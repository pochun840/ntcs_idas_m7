<?php

declare(strict_types=1);

/** Discovers active IPv4 networks and validates controller targets. */
final class JobConfigNetworkService
{
    private const MAX_TARGETS = 253;

    private $commandRunner;
    private $networks;

    public function __construct(?callable $commandRunner = null)
    {
        $this->commandRunner = $commandRunner ?: static function ($command) {
            $output = @shell_exec($command);
            return is_string($output) ? $output : '';
        };
    }

    public static function maxTargets(): int
    {
        return self::MAX_TARGETS;
    }

    public function networkInfo(): array
    {
        $networks = $this->localNetworks();
        return [
            'networks' => $networks,
            'preferred_ip' => isset($networks[0]['ip']) ? $networks[0]['ip'] : null,
            'max_targets' => self::MAX_TARGETS,
        ];
    }

    public function normalizeTargets($targets): array
    {
        if (!is_array($targets) || $targets === []) {
            throw new InvalidArgumentException('At least one target IPv4 address is required');
        }

        $result = [];
        foreach ($targets as $target) {
            $ip = trim((string)$target);
            if ($ip === '') continue;
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new InvalidArgumentException('Invalid target IPv4 address: ' . $ip);
            }
            if ($ip === '0.0.0.0' || strpos($ip, '127.') === 0 || strpos($ip, '169.254.') === 0 || $ip === '192.168.7.7') {
                throw new InvalidArgumentException('Target IPv4 address is not allowed: ' . $ip);
            }
            $result[$ip] = $ip;
        }

        $result = array_values($result);
        if ($result === []) throw new InvalidArgumentException('At least one target IPv4 address is required');
        if (count($result) > self::MAX_TARGETS) {
            throw new InvalidArgumentException('At most ' . self::MAX_TARGETS . ' target controllers are allowed');
        }
        return $result;
    }

    public function localNetworks(): array
    {
        if (is_array($this->networks)) return $this->networks;
        $run = $this->commandRunner;
        $ipBinary = is_executable('/sbin/ip') ? '/sbin/ip' : (is_executable('/usr/sbin/ip') ? '/usr/sbin/ip' : 'ip');
        $output = (string)call_user_func($run, $ipBinary . ' -o -4 addr show up scope global 2>/dev/null');
        $routeOutput = (string)call_user_func($run, $ipBinary . ' -o -4 route get 1.1.1.1 2>/dev/null');
        $preferredIp = null;
        if (preg_match('/\bsrc\s+([0-9.]+)/', $routeOutput, $m)) $preferredIp = $m[1];

        $networks = [];
        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            if (!preg_match('/^\d+:\s+([^\s]+)\s+inet\s+([0-9.]+)\/(\d+)/', trim($line), $m)) continue;
            $interface = preg_replace('/@.*$/', '', (string)$m[1]);
            $ip = (string)$m[2];
            $prefix = (int)$m[3];
            if ($prefix < 1 || $prefix > 32) continue;
            if (preg_match('/^(lo|docker\d*|br-|veth|virbr|tun|tap|wg|tailscale)/i', $interface)) continue;
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
            if (strpos($ip, '127.') === 0 || strpos($ip, '169.254.') === 0 || $ip === '192.168.7.7') continue;
            $networks[$ip . '/' . $prefix] = [
                'interface' => $interface,
                'ip' => $ip,
                'prefix' => $prefix,
                'netmask' => $this->prefixToNetmask($prefix),
                'network' => $this->networkAddress($ip, $prefix),
                'broadcast' => $this->broadcastAddress($ip, $prefix),
                'cidr' => $this->networkAddress($ip, $prefix) . '/' . $prefix,
            ];
        }

        $networks = array_values($networks);
        usort($networks, static function ($a, $b) use ($preferredIp) {
            if ($preferredIp !== null) {
                if ($a['ip'] === $preferredIp && $b['ip'] !== $preferredIp) return -1;
                if ($b['ip'] === $preferredIp && $a['ip'] !== $preferredIp) return 1;
            }
            return strcmp($a['interface'], $b['interface']);
        });
        $this->networks = $networks;
        return $networks;
    }

    public function matchedNetwork(string $targetIp, ?array $networks = null)
    {
        $networks = $networks ?? $this->localNetworks();
        foreach ($networks as $network) {
            if ($this->sameSubnet($targetIp, $network['ip'], (int)$network['prefix'])) return $network;
        }
        return null;
    }

    public function isLocalIp(string $ip, ?array $networks = null): bool
    {
        $networks = $networks ?? $this->localNetworks();
        foreach ($networks as $network) if ($network['ip'] === $ip) return true;
        return false;
    }

    public function isUsableHostAddress(string $ip, array $network): bool
    {
        $prefix = (int)$network['prefix'];
        if ($prefix >= 31) return true;
        return $ip !== $network['network'] && $ip !== $network['broadcast'];
    }

    public function isRemoteAddressOnLocalSubnet(string $remoteAddress): bool
    {
        if (strpos($remoteAddress, '::ffff:') === 0) $remoteAddress = substr($remoteAddress, 7);
        if ($remoteAddress === '127.0.0.1' || $remoteAddress === '::1') return true;
        if (!filter_var($remoteAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
        return $this->matchedNetwork($remoteAddress) !== null;
    }

    private function sameSubnet(string $first, string $second, int $prefix): bool
    {
        $firstLong = ip2long($first);
        $secondLong = ip2long($second);
        if ($firstLong === false || $secondLong === false) return false;
        if ($prefix <= 0) return true;
        $mask = (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
        return (($firstLong & $mask) === ($secondLong & $mask));
    }

    private function prefixToNetmask(int $prefix): string
    {
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip($mask);
    }

    private function networkAddress(string $ip, int $prefix): string
    {
        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip($long & $mask);
    }

    private function broadcastAddress(string $ip, int $prefix): string
    {
        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip(($long & $mask) | ((~$mask) & 0xFFFFFFFF));
    }
}
