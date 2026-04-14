<?php
declare(strict_types=1);
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="brand-block">
        <div class="brand-mark">DW</div>
        <div>
            <strong>DOAN_WEB</strong>
            <p>Billiards Control Hub</p>
        </div>
    </div>

    <nav class="side-nav">
        <?php foreach ($pages as $key => $meta): ?>
            <?php $isActive = $currentPage === $key; ?>
            <a class="nav-link <?php echo $isActive ? "is-active" : ""; ?>" href="index.php?page=<?php echo h($key); ?>">
                <i class="<?php echo h($meta["icon"]); ?>"></i>
                <span><?php echo h($meta["title"]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
        <p>Shift summary</p>
        <div class="foot-card">
            <span>Open tables</span>
            <strong>11</strong>
        </div>
        <div class="foot-card">
            <span>Pending bills</span>
            <strong>6</strong>
        </div>
        <a class="logout-link" href="logout.php"><i class="ri-logout-box-r-line"></i> Logout</a>
    </div>
</aside>
