-- ================================================================
-- MAPAWA BAPTIST YOUTH MINISTRY — Database Schema
-- MySQL / MariaDB
-- HOW TO IMPORT:
--   Option A (terminal): mysql -u root -p < database.sql
--   Option B (phpMyAdmin): select server root → SQL tab → paste → Go
-- ================================================================

CREATE DATABASE IF NOT EXISTS mapawa_youth
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mapawa_youth;

-- ────────────────────────────────────────────────────────────────
-- ADMIN USERS
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(80)  NOT NULL UNIQUE,
  email       VARCHAR(180) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('super_admin','admin','editor') NOT NULL DEFAULT 'editor',
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  last_login  DATETIME,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default admin: username = admin | password = MBC@Admin2025!
INSERT IGNORE INTO admin_users (username, email, password, role)
VALUES (
  'admin',
  'admin@mapawabaptist.org',
  '$2y$10$KoSoqrso2fpPe6wSODUBm.Q4RYr8dAELUpSETmgJuXB2YOuOawbyu',
  'super_admin'
);

-- ────────────────────────────────────────────────────────────────
-- SERMONS
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sermons (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug            VARCHAR(160) NOT NULL UNIQUE,
  series_number   VARCHAR(10),
  title           VARCHAR(220) NOT NULL,
  speaker         VARCHAR(120),
  series_name     VARCHAR(160),
  sermon_type     ENUM('series','standalone') NOT NULL DEFAULT 'series',
  scripture_ref   VARCHAR(120),
  preached_date   DATE,
  duration        VARCHAR(30),
  description     TEXT,
  key_points      TEXT,
  audio_path      VARCHAR(300),
  video_path      VARCHAR(300),
  youtube_url     VARCHAR(300),
  pdf_path        VARCHAR(300),
  thumbnail_path  VARCHAR(300),
  status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  view_count      INT UNSIGNED NOT NULL DEFAULT 0,
  created_by      INT UNSIGNED,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
  FULLTEXT KEY ft_sermon (title, description, speaker, scripture_ref)
);

INSERT IGNORE INTO sermons
  (slug, series_number, title, speaker, series_name, sermon_type, scripture_ref, description, status)
VALUES
  ('foundation-of-the-youth-ministry', '001', 'Foundation of the Youth Ministry',
   'Youth Pastor', 'Ministry Foundations', 'series', 'Acts 2:42-47',
   'What is the church, what is the youth ministry, and why does it matter?', 'published'),

  ('walking-in-the-spirit', '002', 'Walking in the Spirit',
   'Youth Pastor', 'Ministry Foundations', 'series', 'Galatians 5:16-25',
   'Discover what it means to surrender daily to the Holy Spirit.', 'draft'),

  ('rooted-and-grounded-in-love', '003', 'Rooted and Grounded in Love',
   'Youth Pastor', 'Ministry Foundations', 'series', 'Ephesians 3:14-21',
   'Understanding the depth of God\'s love as the foundation of our identity.', 'draft'),

  ('the-cross-changes-everything', '004', 'The Cross Changes Everything',
   'Youth Pastor', NULL, 'standalone', 'Romans 6:1-14',
   'Why the cross is not just the starting point but the centre of all Christian living.', 'draft');

-- ────────────────────────────────────────────────────────────────
-- EVENTS
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(220) NOT NULL,
  slug            VARCHAR(220) NOT NULL UNIQUE,
  category        ENUM('retreat','outreach','worship','training','general') NOT NULL DEFAULT 'general',
  event_date      DATE NOT NULL,
  event_time      TIME,
  end_date        DATE,
  location        VARCHAR(220),
  open_to         VARCHAR(220),
  theme           VARCHAR(220),
  requires_reg    TINYINT(1) NOT NULL DEFAULT 0,
  description     TEXT,
  image_path      VARCHAR(300),
  status          ENUM('upcoming','ongoing','past','cancelled') NOT NULL DEFAULT 'upcoming',
  created_by      INT UNSIGNED,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO events
  (title, slug, category, event_date, event_time, location, open_to, theme, requires_reg, description, status)
VALUES
  ('Youth Retreat 2025', 'youth-retreat-2025', 'retreat',
   '2025-06-14', '08:00:00', 'Mapawa Nature Park', 'All Registered Youth (13-30)', 'Surrender and Sent',
   1, 'Two life-changing days of worship, Bible teaching, prayer, and fellowship.', 'upcoming'),

  ('Community Outreach Day', 'community-outreach-2025', 'outreach',
   '2025-06-28', '08:00:00', 'Barangay Grounds', 'All Youth and Families', NULL,
   0, 'Join us as we serve our neighbors with feeding, prayer, and the gospel.', 'upcoming'),

  ('Worship Night', 'worship-night-july-2025', 'worship',
   '2025-07-05', '18:00:00', 'Main Sanctuary', 'All Youth and Families', NULL,
   0, 'An evening of praise, prayer and seeking God together.', 'upcoming'),

  ('Leadership Training', 'leadership-training-2025', 'training',
   '2025-07-19', '09:00:00', 'Fellowship Hall', 'Youth Leaders', NULL,
   1, 'Equipping the next generation of servant leaders in the church and community.', 'upcoming');

-- ────────────────────────────────────────────────────────────────
-- EVENT REGISTRATIONS
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS event_registrations (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id   INT UNSIGNED NOT NULL,
  name       VARCHAR(160) NOT NULL,
  email      VARCHAR(180),
  phone      VARCHAR(30),
  age_group  VARCHAR(60),
  notes      TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- ────────────────────────────────────────────────────────────────
-- PRAYER REQUESTS
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS prayer_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120),
  category      VARCHAR(80),
  request       TEXT NOT NULL,
  is_anonymous  TINYINT(1) NOT NULL DEFAULT 0,
  is_public     TINYINT(1) NOT NULL DEFAULT 0,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  admin_note    TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ────────────────────────────────────────────────────────────────
-- CONTACT MESSAGES
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(160) NOT NULL,
  email       VARCHAR(180),
  age_group   VARCHAR(60),
  subject     VARCHAR(220),
  message     TEXT NOT NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  replied_at  DATETIME,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ────────────────────────────────────────────────────────────────
-- GALLERY
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gallery_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(300) NOT NULL,
  caption     VARCHAR(300),
  category    ENUM('worship','outreach','retreat','fellowship','other') NOT NULL DEFAULT 'other',
  is_visible  TINYINT(1) NOT NULL DEFAULT 1,
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- ────────────────────────────────────────────────────────────────
-- ADMIN SESSION LOG
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_sessions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NOT NULL,
  ip_address  VARCHAR(45),
  user_agent  VARCHAR(300),
  logged_in   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  logged_out  DATETIME,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);