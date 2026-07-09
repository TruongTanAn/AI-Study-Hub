<?php
$current_keyword = isset($_GET['keyword']) ? htmlspecialchars(trim($_GET['keyword'])) : '';
?>

<div class="docs-toolbar">
    <div class="docs-search-wrapper" id="docs-search-wrapper">
        <form method="GET" action="" id="docs-search-form" autocomplete="off">
            <?php if (!empty($_GET['subject_id'])): ?>
                <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($_GET['subject_id']); ?>">
            <?php endif; ?>
            <i class="fas fa-search docs-search-icon" aria-hidden="true"></i>
            <input
                type="search"
                id="docs-search-input"
                name="keyword"
                class="docs-search-input"
                placeholder="Tìm kiếm tài liệu..."
                value="<?php echo $current_keyword; ?>"
                aria-label="Tìm kiếm tài liệu"
                spellcheck="false"
            >
            <button
                type="button"
                id="docs-search-clear"
                class="docs-search-clear"
                aria-label="Xóa từ khóa"
                style="<?php echo $current_keyword ? '' : 'display:none'; ?>"
            >
                <i class="fas fa-times"></i>
            </button>
        </form>
    </div>

    <?php include __DIR__ . '/filter_subject.php'; ?>

    <span class="docs-result-count" id="docs-result-count" aria-live="polite"></span>
</div>
