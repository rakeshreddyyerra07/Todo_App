-- Run this migration once in the todo_app database before using the enhanced task screens.
ALTER TABLE tasks
  ADD COLUMN is_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium' AFTER is_completed,
  ADD COLUMN progress ENUM('Todo', 'In Progress', 'Review', 'Done') NOT NULL DEFAULT 'Todo' AFTER priority;

-- Existing completed items may be updated manually if needed. Completion is intentionally separate from progress.
