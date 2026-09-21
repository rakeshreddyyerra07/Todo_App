# Todo App - CodeIgniter 4 version

This package is an **overlay** for your existing `Todo_App_CI4` project.
Extract it INTO the project folder and choose "Replace" when asked.
Nothing here touches `vendor/`, `.env` or your existing login code
(`AuthController`, `LoginLogModel`, `views/auth/login.php`).

## 1. Files

| Path | What it is |
|---|---|
| `app/Config/Routes.php` | all routes (replaces yours) |
| `app/Config/Filters.php` | your file + the `auth` filter alias |
| `app/Filters/AuthFilter.php` | login check for pages and AJAX calls |
| `app/Helpers/task_helper.php` | view helpers from the original `index.php` |
| `app/Controllers/` | `TaskController` (replaced), `BoardController`, `CommentController`, `AttachmentController`, `AccountController` (register / forgot / reset / logout), `Home` |
| `app/Models/` | `TaskModel`, `BoardModel`, `UserModel` (updated), `CommentModel`, `AttachmentModel` |
| `app/Database/Migrations/2026-09-21-100000_CreateTodoTables.php` | creates missing tables, upgrades old ones, deletes nothing |
| `app/Views/tasks/` | `index` (board), `add`, `edit`, `view` |
| `app/Views/auth/` | `register`, `forgot_password`, `reset_password` |
| `public/assets/` | `style.css`, `css/tasks.css`, `js/tasks.js` (moved out of the page) |

## 2. Commands (Windows cmd, inside the project folder)

```
:: 1. .env  ->  add / un-comment these lines
::      CI_ENVIRONMENT = development
::      app.baseURL = 'http://localhost:8080/'
::      app.indexPage = ''
::    (database.default.* is already in your .env)

:: 2. create / upgrade the tables
php spark migrate

:: 3. (optional) bring over the files uploaded in the original app
xcopy /E /I ..\Todo_App\uploads\task_files public\uploads\task_files

:: 4. run
php spark serve
```

Open http://localhost:8080 .

Make yourself an admin (only admins get the per-column "+" button and task delete):

```
mysql -u root todo_app -e "UPDATE users SET role='admin' WHERE email='you@example.com';"
```

## 3. Routes

| URL | Original file |
|---|---|
| `GET /` | `index.php` |
| `/login`, `/register`, `/forgot-password`, `/reset-password`, `/logout` | `auth/*.php` |
| `GET /tasks?board_id=&search=&progress=` | `task/index.php` |
| `GET /tasks/add`, `POST /tasks/create` | `task/add.php` |
| `GET /tasks/view/ID`, `/tasks/edit/ID`, `/tasks/delete/ID`, `/tasks/toggle/ID` | `view.php`, `edit.php`, `delete.php`, `toggle_complete.php` |
| `POST /tasks/update-field`, `POST /tasks/update-progress` | `index.php` (action=update_task_field / update_progress) |
| `GET /tasks/comments/ID`, `POST /tasks/comments/add` | `get_comments.php`, `add_comment.php` |
| `GET /tasks/attachments/ID`, `POST .../upload`, `POST .../delete`, `GET .../view/ID` | `get_attachments.php`, `upload_attachment.php`, `delete_attachment.php`, `view_attachment.php` |
| `POST /boards/create`, `POST /boards/delete` | `index.php` (action=create_board / delete_board) |

## 4. Behaviour

The screens, buttons, validations, messages, permissions and JSON answers were
ported one-to-one from the original files, quirks included. Only the plumbing
differs:

* CSS and JavaScript of the board page live in `public/assets/` instead of
  being inline (same code, same behaviour).
* Uploaded files are stored in `public/uploads/task_files`, the same place as
  the original (`uploads/task_files`), and are opened by the same direct link.
* The migration only adds what the newer code already needs: `tasks.board_id`
  when missing, the "Pending" value of `tasks.progress`, and - only when
  `tasks.is_completed` is still a number - it is converted to text
  (0 -> Incomplete, 1 -> Completed) because the original code stores
  Completed / Pending / Incomplete. No rows are deleted.

## 5. Things you should know

* **Rotate the database password** that is in the original `config/database.php`
  (it is also in the git history). Keep credentials in `.env` only.
* **Forgot password** still shows the reset link on screen, exactly like the
  original. Anyone who knows an email address can therefore reset that account.
  Send the link by e-mail before going live.
* Uploaded files can be opened directly by anyone who knows the file name
  (as in the original). `/tasks/attachments/view/ID` is the login-protected
  alternative if you ever want to switch.
* CSRF protection is off (as in the original). To turn it on, uncomment `'csrf'`
  in `app/Config/Filters.php` and send the token with the `fetch()` calls in
  `public/assets/js/tasks.js`.
* Attachments are limited to 10 MB by the code, but PHP's own
  `upload_max_filesize` / `post_max_size` (often 2 MB) apply first.
