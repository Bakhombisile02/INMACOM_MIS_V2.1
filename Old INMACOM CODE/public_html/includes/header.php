<header class="navbar navbar-header navbar-header-fixed">
    <a href="#" id="mainMenuOpen" class="burger-menu"><i data-feather="menu"></i></a>
    <div class="navbar-brand">
        <div><img src="./assets/img/Logo.png" alt="INMACOM" height="50"></div>
    </div><!-- navbar-brand -->
    <div id="navbarMenu" class="navbar-menu-wrapper">
        <div class="navbar-menu-header">
            <!-- <a href="./" class="df-logo">INMA<span>COM</span></a> -->
            <a href="./" class="df-logo"><img src="../assets/img/Logo.png" alt=""></a>
            <a id="mainMenuClose" href="#"><i data-feather="x"></i></a>
        </div><!-- navbar-menu-header -->
        <ul class="nav navbar-menu">
            <li class="nav-item"><a href="./" class="nav-link">INMACOM MIS </a></li>
            <li class="nav-item with-sub">
                <a href="#" class="nav-link">Unverified Data </a>
                <ul class="navbar-menu-sub">
                    <li class="nav-sub-item"><a href="hydrology-current.php" class="nav-sub-link">Current Status</a></li>
                    <li class="nav-sub-item"><a href="hydrology-historic.php" class="nav-sub-link">Historical Data</a></li>
                </ul>
            </li>
            <li class="nav-item"><a href="verified-data.php" class="nav-link">Verified Data </a></li>
            <li class="nav-item"><a href="documents.php" class="nav-link">Documents </a></li>
            <li class="nav-item with-sub">
                <a href="#" class="nav-link">External Links </a>
                <ul class="navbar-menu-sub">
                    <li class="nav-sub-item"><a href="www.hydronet.co.za" class="nav-sub-link">Hydronet</a></li>
                </ul>
            </li>
            <?php if (! isset($_SESSION['login'])) { ?>
                <li class="nav-item"><a href="login.php" class="nav-link">Login </a></li>
            <?php } ?>
            <?php if (isset($_SESSION['login'])) { ?>
                <li class="nav-item"><a href="admin/index.php" class="nav-link">Dashboard </a></li>
            <?php } ?>

        </ul>
        <div class="navbar-right">
            <div id="google_translate_element"></div>
        </div>
    </div><!-- navbar-menu-wrapper -->

</header>