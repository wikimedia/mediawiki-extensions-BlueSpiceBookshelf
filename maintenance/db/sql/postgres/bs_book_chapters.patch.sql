-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/BlueSpiceBookshelf/maintenance/db/bs_book_chapters.patch.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE  bs_book_chapters
ADD  chapter_book_id INT NOT NULL;
ALTER TABLE  bs_book_chapters
ADD  chapter_namespace TEXT DEFAULT NULL;
ALTER TABLE  bs_book_chapters
ADD  chapter_name TEXT NOT NULL;
ALTER TABLE  bs_book_chapters
DROP  chapter_book_namespace;
ALTER TABLE  bs_book_chapters
DROP  chapter_book_title;
ALTER TABLE  bs_book_chapters
DROP  chapter_page_namespace;
ALTER TABLE  bs_book_chapters
DROP  chapter_page_title;
ALTER TABLE  bs_book_chapters ALTER chapter_title
DROP  NOT NULL;