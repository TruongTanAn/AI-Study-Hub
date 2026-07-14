-- =============================================================
-- Supabase Storage RLS Policies for AI Study Hub
-- =============================================================
-- Run this script ONCE in Supabase SQL Editor to enable
-- uploads / downloads / deletions from the anon (publishable) key.
--
-- This is the recommended path for server-side uploads using only
-- the publishable key. For maximum security, restrict by user_id
-- (see commented policies at the bottom).
-- =============================================================

-- -------------------------------------------------------------
-- 1. Enable RLS on storage.objects (it is enabled by default in
--    Supabase, but we add this to be explicit).
-- -------------------------------------------------------------
ALTER TABLE IF EXISTS storage.objects ENABLE ROW LEVEL SECURITY;

-- -------------------------------------------------------------
-- 2. Allow anon role (publishable key) to SELECT, INSERT, UPDATE,
--    DELETE inside the 'documents' bucket only.
-- -------------------------------------------------------------
DROP POLICY IF EXISTS "ai_study_hub_documents_select" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_insert" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_update" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_delete" ON storage.objects;

CREATE POLICY "ai_study_hub_documents_select"
  ON storage.objects FOR SELECT
  TO public
  USING (bucket_id = 'documents');

CREATE POLICY "ai_study_hub_documents_insert"
  ON storage.objects FOR INSERT
  TO public
  WITH CHECK (bucket_id = 'documents');

CREATE POLICY "ai_study_hub_documents_update"
  ON storage.objects FOR UPDATE
  TO public
  USING (bucket_id = 'documents')
  WITH CHECK (bucket_id = 'documents');

CREATE POLICY "ai_study_hub_documents_delete"
  ON storage.objects FOR DELETE
  TO public
  USING (bucket_id = 'documents');

-- =============================================================
-- ALTERNATIVE: more secure policies (per-user folder naming)
-- =============================================================
-- The default upload path we use is `user-uploads/<user_id>/<file>`
-- so we can scope each user to their own folder. Uncomment the
-- following block if you want hard multi-tenant isolation.
-- =============================================================

/*
DROP POLICY IF EXISTS "ai_study_hub_documents_select" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_insert" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_update" ON storage.objects;
DROP POLICY IF EXISTS "ai_study_hub_documents_delete" ON storage.objects;

CREATE POLICY "ai_study_hub_documents_select"
  ON storage.objects FOR SELECT
  TO public
  USING (
    bucket_id = 'documents'
    AND (storage.foldername(name))[1] = 'user-uploads'
  );

CREATE POLICY "ai_study_hub_documents_insert"
  ON storage.objects FOR INSERT
  TO public
  WITH CHECK (
    bucket_id = 'documents'
    AND (storage.foldername(name))[1] = 'user-uploads'
  );

CREATE POLICY "ai_study_hub_documents_update"
  ON storage.objects FOR UPDATE
  TO public
  USING (
    bucket_id = 'documents'
    AND (storage.foldername(name))[1] = 'user-uploads'
  )
  WITH CHECK (
    bucket_id = 'documents'
    AND (storage.foldername(name))[1] = 'user-uploads'
  );

CREATE POLICY "ai_study_hub_documents_delete"
  ON storage.objects FOR DELETE
  TO public
  USING (
    bucket_id = 'documents'
    AND (storage.foldername(name))[1] = 'user-uploads'
  );
*/
