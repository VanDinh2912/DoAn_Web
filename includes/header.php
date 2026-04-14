<?php
declare(strict_types=1);

$todayLabel = date("l, d M Y");
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="icon-btn" id="mobileMenuToggle" aria-label="Open menu">
            <i class="ri-menu-3-line"></i>
        </button>

        <div class="title-wrap">
            <h1><?php echo h($pageTitle); ?></h1>
            <p><?php echo h($todayLabel); ?></p>
        </div>
    </div>

    <div class="topbar-right">
        <div class="search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" placeholder="Search table, order, customer..." aria-label="Search">
        </div>

        <div class="notif-wrap">
            <button class="icon-btn" id="notifToggle" aria-label="Notifications">
                <i class="ri-notification-3-line"></i>
                <span class="dot"></span>
            </button>
            <div class="notif-panel" id="notifPanel">
                <h3>Notifications</h3>
                <div class="notif-item">
                    <i class="ri-alert-line"></i>
                    <div>
                        <p>Pepsi can stock is below minimum level</p>
                        <small>5 minutes ago</small>
                    </div>
                </div>
                <div class="notif-item">
                    <i class="ri-billiards-line"></i>
                    <div>
                        <p>Table A1 has been active for over 2 hours</p>
                        <small>12 minutes ago</small>
                    </div>
                </div>
                <div class="notif-item">
                    <i class="ri-receipt-line"></i>
                    <div>
                        <p>Order HD2205 completed successfully</p>
                        <small>20 minutes ago</small>
                    </div>
                </div>
            </div>
        </div>

        <button class="profile-btn" aria-label="Profile">
            <span class="profile-avatar">DW</span>
            <span class="profile-meta">
                <strong><?php echo h($activeUser); ?></strong>
                <small><?php echo h($activeRole); ?></small>
            </span>
        </button>
    </div>
</header>
