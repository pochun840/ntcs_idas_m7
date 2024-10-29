<?php 

function includecss_file($part, $cssFileName) {
    $queryString = $_SERVER['QUERY_STRING'];
    $queryStringWithoutUrl = str_replace('url=', '', $queryString);
    $parts = explode('/', $queryStringWithoutUrl);
    $firstPart = $parts[0];
    $extension = pathinfo($cssFileName, PATHINFO_EXTENSION);

    if($firstPart == $part){
        if($extension == 'css'){ ?>
            <link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/<?php echo $cssFileName; ?>?v=<?php echo date('YmdHis');?>">
        <?php }elseif($extension == 'js'){ ?>
            <script src="<?php echo URLROOT; ?>js/<?php echo $cssFileName; ?>?v=<?php echo date('YmdHis'); ?>"></script>
        <?php }
    }
}
?>
    <script src="<?php echo URLROOT; ?>js/jquery-3.7.1.min.js"></script>
 
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/jquery_data_Tables.css?v=202408211600">

    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/datatables.min.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css">

    <script src="<?php echo URLROOT; ?>js/all.js?v=202408191700"></script>
    <script src="<?php echo URLROOT; ?>js/echarts_min.js"></script>
    <script src="<?php echo URLROOT; ?>js/jquery_data_Tables.js?v=202408211500"></script>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/flatpickr.min.css" type="text/css">

    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/alertify_min.css?v=202408211500"/>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/default_min.css?v=202408211500"/>
    <script src="<?php echo URLROOT; ?>js/alertify_min.js?v=202408211600"></script>

    <?php 
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $isMobile = preg_match('/Mobile|Android|Silk|Kindle|BlackBerry|Opera Mini|Opera Mobi/', $userAgent);

        #判斷是否為行動裝置 是的話就include 行動版的css
        if ($isMobile){
            //includecss_file("Tools", "tcc_tool.css");
            //includecss_file("Inputs", "tcc_input_m.css");
            //includecss_file("Outputs", "tcc_output_m.css");
            //includecss_file("Sequences", "tcc_seq_m.css");
            //includecss_file("Jobs", "tcc_jobs_m.css");
            //includecss_file("Step", "tcc_step_m.css");
           //includecss_file("Dashboards","tcc_operation_m.css");
            //includecss_file("Data","tcc_data.css");
            //includecss_file("Settings","tcc_setting_m.css");
    
        }else{
            //includecss_file("Tools", "tcc_tool.css");
            //includecss_file("Inputs", "tcc_input.css");
            //includecss_file("Outputs", "tcc_output.css");
            //includecss_file("Sequences", "tcc_seq.css");
            //includecss_file("Jobs", "tcc_jobs.css");
            //includecss_file("Step", "tcc_step.css");
            //includecss_file("Dashboards","tcc_operation.css");
            //includecss_file("Data","tcc_data.css");
            //includecss_file("Settings","tcc_setting.css");           
        }

        # 載入js 
        //includecss_file("Inputs", "inputs.js");
        //includecss_file("Outputs", "outputs.js");
        includecss_file("Jobs", "jobs.js");
        includecss_file("Data", "data.js");
        includecss_file("Sequences", "seq.js");
        includecss_file("Step", "step.js");
        includecss_file("Settings", "settings.js");

    ?>

    <script src="<?php echo URLROOT; ?>js/flatpickr.js?v=202406131200"></script>
    <script src="<?php echo URLROOT; ?>js/tcc_data.js?v=202406131200"></script>
    <script src="<?php echo URLROOT; ?>js/flatpickr.js"></script>
    <script src="<?php echo URLROOT; ?>js/flatpickr_zh-tw.js"></script>
    <script src="<?php echo URLROOT; ?>js/jszip.js?v=202406241500"></script>
