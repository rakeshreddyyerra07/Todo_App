# Todo_App_CI4 login fixes

I could not open todo-app.wasmer.app (it blocks automated requests) and had no PHP here,
so these changes are untested. They come from reading your project, its git history
(the original PHP app on `main`) and your `writable/` logs and session files.

Your session files show the admin account (role `admin`) logged in and reached `/tasks`
on http://localhost:8080. The CI4 login code is the same logic as the original
`auth/login.php`. So the admin problem is most likely in the Wasmer deployment or the data,
not in the login logic. The fixes below remove the code-side risks; the table further down finds the rest.

## What changed

| File | Change |
|---|---|
| `app/Controllers/AuthController.php` | `/login` used `has('user_id')` while the auth filter uses `get('user_id')`; a session holding an empty id could bounce between /login and /tasks forever (that is a "508 Loop Detected" on Wasmer). Both now use `get()`. |
| | Session now stores `user_id` as an int and the role trimmed and lower-cased (`Admin ` is now `admin`; empty is `user`, like the original). |
| | Outside API calls (ipify, ipapi, OpenStreetMap) wait at most 4 s (2 s to connect) instead of 10 s each. `php -S` serves one request at a time, so 30 s of waiting could freeze the site. |
| | A failure while saving the login log can no longer block a login. |
| `app/Config/Routes.php` | HTTP methods in upper case. Your log file was 800 KB, mostly one deprecation warning per route on every request. |

## Apply (Windows cmd, inside your project folder)

```
xcopy Todo_App_CI4_fixes\app app /E /Y /I
```

Then redeploy to Wasmer the same way you did before.

## If the admin still cannot log in on todo-app.wasmer.app

Log in as admin on the live site and see what happens:

| What you see | Cause | Fix |
|---|---|---|
| "Invalid email or password." | Wrong email, or the admin's password in the database is not a valid bcrypt hash | Run the SQL check below, then reset the password |
| Login page again, no message, or "too many redirects" / 508 | The session is not saved between requests on the server (`writable/session` not writable or not shared) | Make `writable/` writable; if Wasmer runs more than one instance, switch sessions to the database handler |
| Browser jumps to `http://localhost:8080/...` | `.env` still has `app.baseURL = 'http://localhost:8080/'` and overrides `App.php` | Use `.env.wasmer.example` on the server |
| An error page | Copy the message and file name it shows | Send it to me |
| Reaches /tasks but has no admin buttons | `role` is not exactly `admin` | `UPDATE users SET role='admin' WHERE email='your-admin-email';` |

Check the admin row (run in your Wasmer database console):

```sql
SELECT id, email, role, LENGTH(password) AS pw_len, LEFT(password,4) AS pw_prefix FROM users;
```

A healthy admin row has `role = admin`, `pw_len = 60` and `pw_prefix = $2y$`.

Reset the admin password (cmd):

```
php -r "echo password_hash('ChooseANewPassword', PASSWORD_DEFAULT);"
```

Paste the printed hash into:

```sql
UPDATE users SET password='PASTE_HASH_HERE' WHERE email='your-admin-email';
```

## Security

Your `.env`, `config/database.php` and the git history contain the live database password.
Change that password in Wasmer and put the new one only in the server's `.env`.
This package does not include your `.env`.
