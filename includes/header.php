<?php
declare(strict_types=1);

$todayLabel = date("d/m/Y");
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="icon-btn" id="mobileMenuToggle" aria-label="Mở menu">
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
            <input type="text" placeholder="Tìm bàn, hóa đơn, khách hàng..." aria-label="Tìm kiếm">
        </div>

        <div class="notif-wrap">
            <button class="icon-btn" id="notifToggle" aria-label="Thông báo">
                <i class="ri-notification-3-line"></i>
                <span class="dot"></span>
            </button>
            <div class="notif-panel" id="notifPanel">
                <h3>Thông báo</h3>
                <div class="notif-item">
                    <i class="ri-alert-line"></i>
                    <div>
                        <p>Tồn kho Pepsi lon đã thấp hơn mức tối thiểu</p>
                        <small>5 phút trước</small>
                    </div>
                </div>
                <div class="notif-item">
                    <i class="ri-billiards-line"></i>
                    <div>
                        <p>Bàn A1 đã hoạt động hơn 2 giờ</p>
                        <small>12 phút trước</small>
                    </div>
                </div>
                <div class="notif-item">
                    <i class="ri-receipt-line"></i>
                    <div>
                        <p>Hóa đơn HD2205 đã hoàn tất</p>
                        <small>20 phút trước</small>
                    </div>
                </div>
            </div>
        </div>

        <button class="profile-btn" aria-label="Hồ sơ">
            <span class="profile-avatar">DW</span>
            <span class="profile-meta">
                <strong><?php echo h($activeUser); ?></strong>
                <small><?php echo h($activeRole); ?></small>
            </span>
        </button>
    </div>
</header>
