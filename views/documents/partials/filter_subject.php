<?php
$current_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$subjects = $subjects ?? [
    ['id' => 1, 'name' => 'Toán cao cấp'],
    ['id' => 2, 'name' => 'Lập trình Python'],
    ['id' => 3, 'name' => 'Cơ sở dữ liệu'],
    ['id' => 4, 'name' => 'Mạng máy tính'],
    ['id' => 5, 'name' => 'Trí tuệ nhân tạo'],
    ['id' => 6, 'name' => 'Kỹ thuật phần mềm'],
    ['id' => 7, 'name' => 'Giải tích'],
    ['id' => 8, 'name' => 'Vật lý đại cương'],
];
?>

<div class="docs-filter-wrapper <?php echo $current_subject ? 'has-filter' : ''; ?>" id="docs-filter-wrapper">
    <select id="docs-filter-subject" name="subject_id" class="docs-filter-select" aria-label="Lọc theo môn học">
        <option value="">Tất cả môn học</option>
        <?php foreach ($subjects as $subj): ?>
            <option value="<?php echo (int)$subj['id']; ?>" <?php echo ($current_subject === (int)$subj['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($subj['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <i class="fas fa-chevron-down docs-filter-chevron" aria-hidden="true"></i>
</div>
