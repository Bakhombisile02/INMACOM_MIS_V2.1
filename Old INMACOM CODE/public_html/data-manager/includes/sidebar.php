<aside class="aside aside-fixed">
    <div class="aside-header">
        <a href="index.php">
            <div><img src="../assets/img/Logo.png" alt="INMACOM" height="50"></div>
        </a>
        <a href="#" class="aside-menu-link">
            <i data-feather="menu"></i>
            <i data-feather="x"></i>
        </a>
    </div>
    <div class="aside-body">
        <div class="aside-loggedin">
            <div class="aside-loggedin-user">
                <a href="#loggedinMenu" class="d-flex align-items-center justify-content-between mg-b-2" data-toggle="collapse">
                    <h6 class="tx-semibold mg-b-5"><?php echo $_SESSION['username']; ?></h6>
                    <i data-feather="chevron-down"></i>
                </a>
                <p class="tx-color-03 tx-12 mg-b-0"><?php echo $_SESSION['role']; ?></p>
            </div>
            <div class="collapse" id="loggedinMenu">
                <ul class="nav nav-aside mg-b-0">
                    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="edit"></i> <span>Edit Profile</span></a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="user"></i> <span>View Profile</span></a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link"><i data-feather="log-out"></i> <span>Sign Out</span></a></li>
                </ul>
            </div>
        </div><!-- aside-loggedin -->
        <ul class="nav nav-aside">
            <li class="nav-item active"><a href="index.php" class="nav-link"><i data-feather="pie-chart"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="manage-stations.php" class="nav-link"><i data-feather="map-pin"></i> <span>Stations</span></a></li>
            <li class="nav-item"><a href="flow-level-data.php" class="nav-link"><i data-feather="activity"></i> <span>Flow Levels</span></a></li>
            <li class="nav-item"><a href="dam-level-data.php" class="nav-link"><i data-feather="percent"></i> <span>Dam Levels</span></a></li>
            <li class="nav-item"><a href="water-quality-data.php" class="nav-link"><i data-feather="thermometer"></i> <span>Water Quality</span></a></li>
            <li class="nav-item"><a href="rainfall-data.php" class="nav-link"><i data-feather="cloud-rain"></i> <span>Rainfall</span></a></li>
            <li class="nav-item"><a href="groundwater-data.php" class="nav-link"><i data-feather="sunset"></i> <span>Groundwater</span></a></li>
            <!-- <li class="nav-item"><a href="file-manager.php" class="nav-link"><i data-feather="folder"></i> <span>Document Storage</span></a></li> -->
            
        </ul>
    </div>
</aside>