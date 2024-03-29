-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/BlueSpiceBookshelf/maintenance/db/bs_book_chapters.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE bs_book_chapters (
  chapter_id SERIAL NOT NULL,
  chapter_book_id INT NOT NULL,
  chapter_namespace TEXT DEFAULT NULL,
  chapter_title TEXT DEFAULT NULL,
  chapter_name TEXT NOT NULL,
  chapter_number TEXT NOT NULL,
  chapter_type TEXT NOT NULL,
  PRIMARY KEY(chapter_id)
);

CREATE INDEX chapter_id_idx ON bs_book_chapters (chapter_id);
