const DOCS_CONFIG = {
    DEBOUNCE_MS: 400,
    PER_PAGE_DEFAULT: 9,
    MOBILE_BREAKPOINT: 768,
    SEARCH_ENDPOINT:    '../backend/search_document.php',
    FILTER_ENDPOINT:    '../backend/filter_document.php',
    DOCUMENTS_ENDPOINT: '../backend/upload_status.php',
};

const MOCK_SUBJECTS = [
    { id: 1, name: 'Toán cao cấp' },
    { id: 2, name: 'Lập trình Python' },
    { id: 3, name: 'Cơ sở dữ liệu' },
    { id: 4, name: 'Mạng máy tính' },
    { id: 5, name: 'Trí tuệ nhân tạo' },
    { id: 6, name: 'Kỹ thuật phần mềm' },
    { id: 7, name: 'Giải tích' },
    { id: 8, name: 'Vật lý đại cương' },
];

let docsState = {
    keyword:      '',
    subjectId:    '',
    currentPage:  1,
    perPage:      DOCS_CONFIG.PER_PAGE_DEFAULT,
    totalItems:   0,
    totalPages:   1,
    allDocuments: [],
    filtered:     [],
};

function debounce(fn, wait) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        keyword:   params.get('keyword')    || '',
        subjectId: params.get('subject_id') || '',
        page:      parseInt(params.get('page') || '1', 10),
    };
}

function pushQueryParams({ keyword, subjectId, page }) {
    const params = new URLSearchParams();
    if (keyword)   params.set('keyword',    keyword);
    if (subjectId) params.set('subject_id', subjectId);
    if (page > 1)  params.set('page',       page);
    const qs = params.toString();
    history.pushState({}, '', qs ? `?${qs}` : window.location.pathname);
}

function initSearch() {
    const input    = document.getElementById('docs-search-input');
    const clearBtn = document.getElementById('docs-search-clear');
    const wrapper  = document.getElementById('docs-search-wrapper');

    if (!input) return;

    const { keyword } = getQueryParams();
    if (keyword) {
        input.value = keyword;
        docsState.keyword = keyword;
        if (clearBtn) clearBtn.style.display = 'block';
    }

    const handleSearch = debounce(function () {
        const val = input.value.trim();
        docsState.keyword     = val;
        docsState.currentPage = 1;
        if (clearBtn) clearBtn.style.display = val ? 'block' : 'none';
        pushQueryParams({ keyword: docsState.keyword, subjectId: docsState.subjectId, page: 1 });
        applyFilterAndSearch();
    }, DOCS_CONFIG.DEBOUNCE_MS);

    input.addEventListener('input', handleSearch);

    const form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            handleSearch();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value           = '';
            docsState.keyword     = '';
            docsState.currentPage = 1;
            clearBtn.style.display = 'none';
            if (wrapper) wrapper.classList.remove('docs-search-active');
            pushQueryParams({ keyword: '', subjectId: docsState.subjectId, page: 1 });
            applyFilterAndSearch();
        });
    }
}

function initFilterSubject() {
    const select  = document.getElementById('docs-filter-subject');
    const wrapper = document.getElementById('docs-filter-wrapper');

    if (!select) return;

    populateSubjectOptions(select, MOCK_SUBJECTS);

    const { subjectId } = getQueryParams();
    if (subjectId) {
        select.value = subjectId;
        docsState.subjectId = subjectId;
        if (wrapper) wrapper.classList.add('has-filter');
    }

    select.addEventListener('change', function () {
        const val = this.value;
        docsState.subjectId   = val;
        docsState.currentPage = 1;
        if (wrapper) wrapper.classList.toggle('has-filter', !!val);
        pushQueryParams({ keyword: docsState.keyword, subjectId: val, page: 1 });
        applyFilterAndSearch();
    });
}

function populateSubjectOptions(select, subjects) {
    const firstOpt = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (firstOpt) select.appendChild(firstOpt);
    subjects.forEach(function (subj) {
        const opt = document.createElement('option');
        opt.value       = subj.id;
        opt.textContent = subj.name;
        select.appendChild(opt);
    });
}

function renderPagination() {
    const wrapper   = document.getElementById('docs-pagination-wrapper');
    const container = document.getElementById('docs-pagination');
    const infoEl    = document.getElementById('docs-pagination-info');

    if (!container) return;

    const { currentPage, totalPages, totalItems, perPage } = docsState;

    if (infoEl) {
        const start = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
        const end   = Math.min(currentPage * perPage, totalItems);
        infoEl.innerHTML = totalItems === 0
            ? 'Không có tài liệu nào'
            : `Hiển thị <strong>${start}–${end}</strong> / <strong>${totalItems}</strong> tài liệu`;
    }

    if (wrapper) wrapper.style.display = totalPages <= 1 ? 'none' : '';
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const isMobile = window.innerWidth < DOCS_CONFIG.MOBILE_BREAKPOINT;

    container.appendChild(makePaginationBtn('← Trước', currentPage - 1, currentPage === 1, false, ['nav-btn']));

    buildPageList(currentPage, totalPages, isMobile).forEach(function (item) {
        if (item === '...') {
            const el = document.createElement('span');
            el.className   = 'docs-page-ellipsis';
            el.textContent = '…';
            container.appendChild(el);
        } else {
            container.appendChild(makePaginationBtn(item, item, false, item === currentPage, []));
        }
    });

    container.appendChild(makePaginationBtn('Sau →', currentPage + 1, currentPage === totalPages, false, ['nav-btn']));
}

function makePaginationBtn(label, targetPage, isDisabled, isActive, extraClasses) {
    const btn = document.createElement('button');
    btn.className   = ['docs-page-btn', ...extraClasses].join(' ');
    btn.textContent = label;
    btn.setAttribute('aria-label', `Trang ${targetPage}`);
    if (isActive) {
        btn.classList.add('active');
        btn.setAttribute('aria-current', 'page');
    }
    if (isDisabled) {
        btn.classList.add('disabled');
        btn.setAttribute('disabled', '');
    } else if (!isActive) {
        btn.addEventListener('click', function () { goToPage(targetPage); });
    }
    return btn;
}

function buildPageList(current, total, isMobile) {
    const pages = new Set();
    pages.add(1);
    pages.add(total);

    if (isMobile) {
        if (current - 1 > 1) pages.add(current - 1);
        pages.add(current);
        if (current + 1 < total) pages.add(current + 1);
    } else {
        const WINDOW = 2;
        for (let i = Math.max(2, current - WINDOW); i <= Math.min(total - 1, current + WINDOW); i++) {
            pages.add(i);
        }
    }

    const sorted = [...pages].sort((a, b) => a - b);
    const result = [];
    sorted.forEach(function (p, i) {
        if (i > 0 && p - sorted[i - 1] > 1) result.push('...');
        result.push(p);
    });
    return result;
}

function goToPage(page) {
    docsState.currentPage = page;
    pushQueryParams({ keyword: docsState.keyword, subjectId: docsState.subjectId, page });
    renderCurrentPage();
    scrollToTop();
}

function applyFilterAndSearch() {
    const keyword   = docsState.keyword.toLowerCase();
    const subjectId = docsState.subjectId ? Number(docsState.subjectId) : null;

    docsState.filtered = docsState.allDocuments.filter(function (doc) {
        const matchKeyword = !keyword ||
            (doc.title || '').toLowerCase().includes(keyword) ||
            (doc.description || '').toLowerCase().includes(keyword);
        const matchSubject = !subjectId || doc.subject_id === subjectId;
        return matchKeyword && matchSubject;
    });

    docsState.totalItems = docsState.filtered.length;
    docsState.totalPages = Math.max(1, Math.ceil(docsState.totalItems / docsState.perPage));
    if (docsState.currentPage > docsState.totalPages) docsState.currentPage = 1;

    renderCurrentPage();
}

function renderCurrentPage() {
    const container = document.getElementById('documents-container');
    const noResults = document.getElementById('docs-no-results');

    if (!container) return;

    const { currentPage, perPage, filtered } = docsState;
    const pageItems = filtered.slice((currentPage - 1) * perPage, currentPage * perPage);

    if (noResults) noResults.classList.toggle('visible', filtered.length === 0);

    if (filtered.length === 0) {
        const grid = container.querySelector('.documents-grid');
        if (grid) grid.style.display = 'none';
        renderPagination();
        updateResultCount();
        return;
    }

    let grid = container.querySelector('.documents-grid');
    if (!grid) {
        grid = document.createElement('div');
        grid.className = 'documents-grid';
        container.appendChild(grid);
    }
    grid.style.display = '';

    const keyword = docsState.keyword;
    grid.innerHTML = pageItems.map(function (doc) {
        const iconClass  = (doc.file_type || '').toLowerCase();
        const iconName   = iconClass === 'pdf' ? 'fa-file-pdf' : iconClass === 'docx' ? 'fa-file-word' : 'fa-file-powerpoint';
        const statusClass = doc.upload_status || 'uploaded';
        const statusText  = doc.status_text   || 'Đã tải lên';

        return `
        <div class="document-card">
            <div class="document-icon ${iconClass}">
                <i class="fas ${iconName}"></i>
            </div>
            <h3 class="document-title">${highlightKeyword(escapeHtml(doc.title || ''), keyword)}</h3>
            <div class="document-meta">
                <span><i class="fas fa-book"></i> ${escapeHtml(doc.subject_name || 'Chưa phân loại')}</span>
                <span><i class="fas fa-file"></i> ${doc.file_size_formatted || ''}</span>
                <span><i class="fas fa-calendar"></i> ${formatDate(doc.upload_date)}</span>
            </div>
            <span class="document-status ${statusClass}">${statusText}</span>
            <div class="document-actions">
                <a href="${doc.cloud_url || doc.file_path || '#'}" class="btn-view" target="_blank">
                    <i class="fas fa-eye"></i> Xem
                </a>
            </div>
        </div>`;
    }).join('');

    renderPagination();
    updateResultCount();
}

function updateResultCount() {
    const el = document.getElementById('docs-result-count');
    if (!el) return;
    el.innerHTML     = `<strong>${docsState.totalItems}</strong> tài liệu`;
    el.style.display = '';
}

function highlightKeyword(html, keyword) {
    if (!keyword) return html;
    const escaped = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return html.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="docs-highlight">$1</mark>');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function scrollToTop() {
    const el = document.getElementById('documents-container');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

window.addEventListener('popstate', function () {
    const { keyword, subjectId, page } = getQueryParams();
    const searchInput  = document.getElementById('docs-search-input');
    const filterSelect = document.getElementById('docs-filter-subject');
    if (searchInput)  searchInput.value  = keyword;
    if (filterSelect) filterSelect.value = subjectId;
    docsState.keyword     = keyword;
    docsState.subjectId   = subjectId;
    docsState.currentPage = page;
    applyFilterAndSearch();
});

let resizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(renderPagination, 200);
});

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('documents-container');
    if (container && container.dataset.perPage) {
        docsState.perPage = parseInt(container.dataset.perPage, 10) || DOCS_CONFIG.PER_PAGE_DEFAULT;
    }

    const { keyword, subjectId, page } = getQueryParams();
    docsState.keyword     = keyword;
    docsState.subjectId   = subjectId;
    docsState.currentPage = page;

    initSearch();
    initFilterSubject();
    loadDocuments();
});

function loadDocuments() {
    if (window.__DOCUMENTS__ && Array.isArray(window.__DOCUMENTS__)) {
        docsState.allDocuments = window.__DOCUMENTS__;
        applyFilterAndSearch();
        return;
    }
    docsState.allDocuments = generateMockDocuments(27);
    applyFilterAndSearch();
}

function generateMockDocuments(count) {
    const types    = ['pdf', 'docx', 'pptx'];
    const statuses = [
        { upload_status: 'uploaded', status_text: 'Đã tải lên' },
        { upload_status: 'pending',  status_text: 'Đang xử lý'  },
    ];
    const sizes = ['1.2 MB', '2.4 MB', '856 KB', '3.1 MB', '540 KB'];

    return Array.from({ length: count }, function (_, i) {
        const subj = MOCK_SUBJECTS[i % MOCK_SUBJECTS.length];
        const stat = statuses[i % statuses.length];
        return {
            id:                  i + 1,
            title:               `Tài liệu ${subj.name} — Chương ${(i % 10) + 1}`,
            description:         `Mô tả tài liệu số ${i + 1}`,
            file_type:           types[i % types.length],
            file_size_formatted: sizes[i % sizes.length],
            upload_date:         new Date(Date.now() - i * 86400000).toISOString(),
            upload_status:       stat.upload_status,
            status_text:         stat.status_text,
            subject_id:          subj.id,
            subject_name:        subj.name,
            cloud_url:           '#',
            user_id:             1,
        };
    });
}
