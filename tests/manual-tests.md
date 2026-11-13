Manual tests (all run with tests/run.sh except the foreign key one)

Test | Expected | Result
Guest GET /discussions/1 | 200, tree with depth 0 to 4 | pass
GET /discussions/abc | 400 (malformed id) | pass
GET /discussions/999 | 404 | pass
Register with valid data | 303, logged in | pass
Register with bad CSRF | 403 | pass
Reply under comment 7 | 303, shows at depth 5 | pass
Second top-level comment | 303, depth 0 | pass
Guest direct POST | 302 to /login, row count unchanged | pass
POST with bad CSRF | 403, row count unchanged | pass
Parent from another discussion | rejected, row count unchanged | pass
Comment with script tag | shown as text, escaped | pass
POST after logout | 302 to /login, row count unchanged | pass
Wrong password | 401 | pass
Seed user (null hash) login | 401 | pass
Insert with parent in another discussion directly in SQL | error 1452 | pass
