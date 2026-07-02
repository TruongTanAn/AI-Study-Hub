<div class="docs-no-results" id="docs-no-results" role="status" aria-live="polite">
    <i class="fas fa-search" aria-hidden="true"></i>
    <h3>Không tìm thấy tài liệu</h3>
    <p>Thử thay đổi từ khóa hoặc chọn môn học khác.</p>
    <button class="docs-reset-link" id="docs-reset-search" type="button">
        <i class="fas fa-undo" aria-hidden="true"></i>
        Xem tất cả tài liệu
    </button>
</div>

<div class="docs-pagination-wrapper" id="docs-pagination-wrapper">
    <p class="docs-pagination-info" id="docs-pagination-info" aria-live="polite"></p>
    <nav class="docs-pagination" id="docs-pagination" aria-label="Phân trang tài liệu"></nav>
</div>

<script>
(function () {
    const resetBtn = document.getElementById('docs-reset-search');
    if (!resetBtn) return;
    resetBtn.addEventListener('click', function () {
        const si = document.getElementById('docs-search-input');
        const fs = document.getElementById('docs-filter-subject');
        const cb = document.getElementById('docs-search-clear');
        if (si) si.value = '';
        if (fs) fs.value = '';
        if (cb) cb.style.display = 'none';
        if (typeof docsState !== 'undefined') {
            docsState.keyword     = '';
            docsState.subjectId   = '';
            docsState.currentPage = 1;
        }
        history.pushState({}, '', window.location.pathname);
        if (typeof applyFilterAndSearch === 'function') applyFilterAndSearch();
    });
}());
</script>
