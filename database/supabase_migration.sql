-- ================================================================
-- AI Study Hub - Supabase Storage Migration SQL (informational)
-- ================================================================
--
-- Database schema KHONG thay doi (giu nguyen schema cua Week 1-5).
-- Migration chi cap nhat du lieu cot `file_path`:
--   - Cu: uploads/pdf/abc.pdf (duong dan local)
--   - Moi: https://aeimwdklvjvvxssayknp.supabase.co/storage/v1/object/public/documents/...
--
-- Viec chuyen doi du lieu duoc thuc hien bang script PHP:
--   /backend/migrate_files_to_supabase.php (chay thu cong 1 lan)
--
-- File SQL nay chi la tham khao, KHONG can chay truc tiep.
-- ================================================================

USE ai_study_hub;

-- Kiem tra trang thai migration hien tai
SELECT
    document_id,
    user_id,
    file_name,
    file_path,
    CASE
        WHEN file_path LIKE 'https://aeimwdklvjvvxssayknp.supabase.co/%'
            THEN 'SUPABASE_OK'
        WHEN file_path LIKE 'uploads/%'
            THEN 'NEEDS_MIGRATION'
        WHEN file_path IS NULL OR file_path = ''
            THEN 'EMPTY'
        ELSE 'UNKNOWN'
    END AS migration_status,
    file_size
FROM documents
ORDER BY document_id ASC;

-- Sau khi chay script migrate, cac dong co migration_status = NEEDS_MIGRATION
-- se duoc cap nhat thanh SUPABASE_OK.
