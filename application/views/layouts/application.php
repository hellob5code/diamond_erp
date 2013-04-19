<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width">
    <title><?=$heading.' &raquo; '.$G_title?></title>  
    <link rel="icon" type="image/png" href="<?=base_url('favicon.ico')?>"> 
    <link rel="stylesheet" href="<?=base_url('css/bootstrap.min.css')?>">
    <link rel="stylesheet" href="<?=base_url('css/font-awesome.min.css')?>">
    <link rel="stylesheet" href="<?=base_url('css/select2.css')?>" />
    <link rel="stylesheet" href="<?=base_url('css/select2bs.css')?>" />
    <link rel="stylesheet" href="<?=base_url('css/typeahead.css')?>" />
    <link rel="stylesheet" href="<?=base_url('css/datepicker.css')?>" />
    <link rel="stylesheet" href="<?=base_url('css/editable.css')?>" />
    <link rel="stylesheet" href="<?=base_url('css/pnotify.default.css')?>"/>
    <link rel="stylesheet" href="<?=base_url('css/main.css')?>" media="screen"/>
</head>
<body>
    <!-- JQUERY LOADING -->
    <script src="<?=base_url('js/jquery.js')?>"></script>
    <!-- END OF JQUERY LOADING -->

    <!-- NAV BAR -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container-fluid">
                <div class="brand" id="diamond-logo">
                    <img src="<?=base_url('img/diamond_erp_logo_30.png')?>" alt="">
                </div>
                <div class="nav-collapse collapse">
                    <?=uif::load('_navigation')?>
                </div>
                <div class="pull-right">
                    <?=uif::linkButton('logout','icon-signout','info')?>
                </div>
            </div>
            
        </div> 
    </nav>
    <!-- END OF NAV BAR -->

    <!-- MAIN CONTENT -->
    <div class="container-fluid">
        <div class="row-fluid">
            <aside class="span2">
                <div class="sidebar-nav">
                    <?=uif::load('_sub_modules')?>
                </div>
            </aside>

            <div class="span10" role="main">
                <?=$yield?>
                <?=uif::load('_pagination')?>
            </div>
        </div>
    </div>
    <!-- END OF MAIN CONTENT -->

    <!-- FOOTER -->
    <footer>
        <?=uif::load('_footer')?>
    </footer>
    <!-- END OF FOOTER -->

    <!-- JAVASCRIPT LOADING -->
    <script src="<?=base_url('js/modernizr.js')?>"></script>
    <script src="<?=base_url('js/bootstrap.min.js')?>"></script>
    <script src="<?=base_url('js/datepicker.js')?>"></script>
    <script src="<?=base_url('js/bootbox.min.js')?>"></script> 
    <script src="<?=base_url('js/pnotify.min.js')?>"></script>
    <script src="<?=base_url('js/select2.js')?>"></script>
    <script src="<?=base_url('js/typeahead.min.js')?>"></script>
    <script src="<?=base_url('js/editable.min.js')?>"></script>
    <script src="<?=base_url('js/plugins.js')?>"></script>
    <script src="<?=base_url('js/scripts.js')?>"></script> 
    <!-- END OF JAVASCRIPT LOADING -->

    <?php if(strlen($this->session->flashdata('message'))): ?>
       <script>
            cd.notify("<?=$this->session->flashdata('message')?>","<?=$this->session->flashdata('type')?>");                
       </script>
    <?php endif; ?>
</body>
</html>