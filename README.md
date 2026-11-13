Nested Forum Lab

A small forum where replies show up as a tree. Anyone can read, only logged in users can post. Built with PHP, PDO and MySQL (MariaDB works too).

Libraries
Bramus Router for routing, Mustache for templates, plain PDO with prepared statements for the database (no Eloquent, so SQL lives only in app/Models).

Install
composer install
Create the database and seed data: mysql -u root < database/schema.sql
The database settings are read from the environment (DB_HOST, DB_NAME, DB_USER, DB_PASS). The defaults are 127.0.0.1, forum_lab, root and an empty password, which is the usual local XAMPP setup. Nothing secret is committed.

Run
php -S localhost:8000 -t public
Open http://localhost:8000/discussions/1
On a real server you would point the web server at public/ and rewrite every request that is not a file to index.php.

How a request moves
public/index.php is the only entry point. The router matches /discussions/1 and calls DiscussionController::show. The controller checks the id, asks the models (Discussion, Comment) for rows, groups the comments by parent_id and builds a nested array with the depth. The Mustache view renders it, with the comment template calling itself for the children.

discussion_id and parent_id
Every comment has a discussion_id. parent_id is the comment it answers, or NULL for a top level comment. A composite foreign key on (discussion_id, parent_id) pointing to comments(discussion_id, id) makes sure a parent is in the same discussion. Depth is not stored, it is counted while building the tree. Top level is 0, and the indent stops growing after depth 8 while the label keeps the real number.

Responses
Non numeric id: the route does not match so the router returns 404. Id that is numeric but not in the database: 404. Guest posting: 401. Bad CSRF token: 403. Bad input: redirected back with a message.

Security notes
Passwords use password_hash and password_verify, a null hash can never log in (the seed users have null hashes). The session id is regenerated on login and logout, cookie is HttpOnly and SameSite=Lax, and Secure on https. Every POST form has a session CSRF token. The user id for a new comment always comes from the session. The parent must exist in the same discussion, and since a new comment can only point to an older one and parents never change, cycles cannot happen. Replies deeper than 20 levels are refused and the renderer stops walking at 50. Mustache escapes all text.

Tests
With the server running and the schema loaded: bash tests/run.sh
It checks 404s, guest posts, bad CSRF, a depth 5 reply, a second top level comment, a cross discussion parent, an HTML comment, logout and wrong passwords. It adds rows to the database, so reload schema.sql to reset.

Optional challenge
public/app.js adds a Hide replies / Show replies button to comments that have replies. The buttons are made by the script, so without JavaScript everything stays visible.

Not done
Avatar upload, Gravatar, rate limits and account recovery.
