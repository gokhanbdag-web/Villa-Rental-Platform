-- Indexes and full-text search for the public blog.
-- Applied once by the versioned browser-safe migration runner.
ALTER TABLE blog_posts
  ADD INDEX idx_blog_public_featured (status, is_featured, published_at, created_at, id),
  ADD INDEX idx_blog_public_views (status, views_count, published_at, created_at),
  ADD FULLTEXT INDEX idx_blog_search (title, excerpt, content);

ALTER TABLE blog_post_category
  ADD INDEX idx_blog_category_post (category_id, post_id);

ALTER TABLE blog_post_tag
  ADD INDEX idx_blog_tag_post (tag_id, post_id);
