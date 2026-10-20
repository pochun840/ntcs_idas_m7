<?php
declare(strict_types=1);
return [
    // Verify with controller records including NG/retry and a SEQ transition.
    // Must represent the product-wide physical screw position, not attempts.
    // No controller data in the source package establishes these semantics.
    'counter_verified' => false,
    'counter_field' => 'last_screw_count',
    'counter_direction' => 'up', // up: 1..N; down: N-1..0 (post-action value)
    // Multiple materials require an explicitly confirmed physical order.
    // Example only: '26010550'=>[1,20], '26010551'=>[21,50].
    'material_ranges' => [],
];
