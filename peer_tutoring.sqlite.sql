-- SQLite version of peer_tutoring database

-- Table: feedback
CREATE TABLE IF NOT EXISTS feedback (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id INTEGER,
  rater_id INTEGER,
  stars INTEGER CHECK (stars BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sessions(id),
  FOREIGN KEY (rater_id) REFERENCES users(id)
);

-- Table: notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  message TEXT,
  is_read INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sample notifications data
INSERT INTO notifications (id, user_id, message, is_read, created_at) VALUES
(11, 10, 'New session request for Development Studies', 1, '2025-10-06 09:29:23'),
(12, 10, 'New session request for Communication Skills', 1, '2025-10-06 09:48:03'),
(13, 14, 'New session request for Data Structures', 1, '2025-10-21 14:50:33'),
(14, 10, 'New session request for Communication Skills', 1, '2025-11-12 08:04:24'),
(15, 14, 'New session request for Data Structures', 1, '2025-11-12 08:08:24'),
(16, 10, 'New session request for Development Studies', 1, '2025-11-12 08:17:42');

-- Table: sessions
CREATE TABLE IF NOT EXISTS sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  learner_id INTEGER,
  tutor_id INTEGER,
  title VARCHAR(255),
  description TEXT,
  start_time DATETIME,
  end_time DATETIME,
  capacity INTEGER DEFAULT 10,
  is_closed INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status TEXT CHECK(status IN ('requested','assigned','accepted','rejected','cancelled','completed')) DEFAULT 'requested',
  FOREIGN KEY (learner_id) REFERENCES users(id),
  FOREIGN KEY (tutor_id) REFERENCES users(id)
);

-- Sample sessions data
INSERT INTO sessions (id, learner_id, tutor_id, title, description, start_time, end_time, capacity, is_closed, created_at, status) VALUES
(11, 11, 10, 'Development Studies', NULL, '2025-10-24 16:33:00', '2025-10-24 17:33:00', 10, 0, '2025-10-06 09:29:23', 'requested'),
(12, 11, 10, 'Communication Skills', NULL, '2025-10-23 17:48:00', '2025-10-23 18:48:00', 10, 0, '2025-10-06 09:48:03', 'accepted'),
(13, 11, 14, 'Data Structures', NULL, '2025-10-31 20:53:00', '2025-10-31 21:53:00', 10, 0, '2025-10-21 14:50:33', 'accepted'),
(14, 15, 10, 'Communication Skills', NULL, '2025-11-29 14:04:00', '2025-11-29 15:04:00', 10, 0, '2025-11-12 08:04:24', 'accepted'),
(15, 10, 14, 'Data Structures', NULL, '2025-11-22 23:11:00', '2025-11-23 00:11:00', 10, 0, '2025-11-12 08:08:24', 'accepted'),
(16, 16, 10, 'Development Studies', NULL, '2025-11-29 16:17:00', '2025-11-29 17:17:00', 10, 0, '2025-11-12 08:17:42', 'accepted');

-- Table: session_registrations
CREATE TABLE IF NOT EXISTS session_registrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id INTEGER NOT NULL,
  student_id INTEGER NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(session_id, student_id),
  FOREIGN KEY (session_id) REFERENCES sessions(id),
  FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Table: tutor_subjects
CREATE TABLE IF NOT EXISTS tutor_subjects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tutor_id INTEGER,
  subject VARCHAR(100),
  year_of_study INTEGER NOT NULL,
  degree_programme VARCHAR(150) NOT NULL,
  FOREIGN KEY (tutor_id) REFERENCES users(id)
);

-- Sample tutor_subjects data
INSERT INTO tutor_subjects (id, tutor_id, subject, year_of_study, degree_programme) VALUES
(12, 10, 'Enterpreneurship', 1, 'BSC in Computer Science'),
(13, 10, 'Development Studies', 1, 'BSC in Computer Science'),
(14, 10, 'Communication Skills', 1, 'BSC in Computer Science'),
(15, 14, 'Data Structures', 1, 'Bsc in computer science'),
(16, 14, 'web technologies', 1, 'Bsc in computer science');

-- Table: tutor_availability
CREATE TABLE IF NOT EXISTS tutor_availability (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tutor_id INTEGER,
  day_of_week VARCHAR(20),
  start_time TIME,
  end_time TIME,
  FOREIGN KEY (tutor_id) REFERENCES users(id)
);

-- Table: users
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  email VARCHAR(100) UNIQUE,
  phone VARCHAR(20),
  degree_programme VARCHAR(100),
  year_of_study INTEGER,
  password_hash VARCHAR(255),
  status TEXT CHECK(status IN ('pending','active','suspended','deactivated')) DEFAULT 'pending',
  deactivation_reason TEXT,
  verification_token VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  profile_pic VARCHAR(255)
);

-- Sample users data
INSERT INTO users (id, first_name, last_name, email, phone, degree_programme, year_of_study, password_hash, status, deactivation_reason, verification_token, created_at, profile_pic) VALUES
(10, 'kemmy', 'doe', 'kemmy@ifm.ac.tz', '0657898989', 'BSC in Computer Science', 1, '$2y$10$bW6KNZ.yOBs66mAz/oFPB.DsWl/221IGs1VnIfEzHbI93OKMEbTZu', 'active', NULL, NULL, '2025-10-04 17:06:01', NULL),
(11, 'dave', 'dave', 'dave@ifm.ac.tz', '0653103227', 'BSC in Computer Science', 1, '$2y$10$Y5M0Z2W/a77TCDNf.5nemuauL5t33c6Ju2hL27oaMIjHRhazj3Xm2', 'active', NULL, '54e050cf36fb635c09ec87bac2a22a37', '2025-10-04 17:23:38', NULL),
(12, 'datius', 'sinner', 'datius@ifm.ac.tz', NULL, NULL, NULL, '$2y$10$9oYyLf/RKVjVTIr5B8dlJO25TrvgaGW2dt3IdGfD66dekeBcLjh6W', 'active', NULL, NULL, '2025-10-06 16:06:18', NULL),
(13, 'admin', 'admin', 'admin@ifm.ac.tz', NULL, NULL, NULL, '$2y$10$Hhcqbrtt8fsTpl9gUNmFf.7bp9xzq7cfibmsQnpQ9ki0BNaFCLKLy', 'active', NULL, NULL, '2025-10-06 16:47:16', NULL),
(14, 'eugen', 'mamboya', 'eugen@ifm.ac.tz', '0787080792', 'Bsc in computer science', 1, '$2y$10$.yn8Wf3Htng5frrHuqORrOEV8nKRnslM7eJYaO6UyfW6B/UP4pkzS', 'active', NULL, NULL, '2025-10-21 14:46:56', 'tutor_14_1761058135.png'),
(15, 'kibwana', 'miruru', 'kibwana@ifm.ac.tz', '0780000000', 'Bsc in computer science', 1, '$2y$10$64jHA9FVc7nxyxZFVfcd.uu5GI3t.h46tg/HfFsF8L7Jhxm6YsuR2', 'active', NULL, NULL, '2025-11-12 08:02:09', 'student_15_1762934609.png'),
(16, 'dennis', 'denis', 'dennis@ifm.ac.tz', '0626540911', 'Bsc in computer science', 1, '$2y$10$/gzlcs4RdW1dyYi0WCG3dukkjpb75Kux66fQXtHnkTfedeciu59WO', 'active', NULL, NULL, '2025-11-12 08:16:05', 'student_16_1762935423.png');

-- Table: user_roles
CREATE TABLE IF NOT EXISTS user_roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  role TEXT CHECK(role IN ('student','tutor','admin')) NOT NULL,
  UNIQUE(user_id, role),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sample user_roles data
INSERT INTO user_roles (id, user_id, role) VALUES
(1, 10, 'student'),
(2, 10, 'tutor'),
(3, 11, 'student'),
(4, 12, 'student'),
(5, 13, 'admin'),
(6, 14, 'tutor'),
(7, 15, 'student'),
(8, 16, 'student');
