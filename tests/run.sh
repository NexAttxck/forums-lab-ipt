#!/usr/bin/env bash
# Manual-ish integration checks. Needs the server on localhost:8000 and the schema loaded.
# Usage: bash tests/run.sh
B=http://localhost:8000
J=$(mktemp); G=$(mktemp)
U=tester$RANDOM
pass=0; fail=0
check() { if [ "$2" = "$3" ]; then echo "PASS $1"; pass=$((pass+1)); else echo "FAIL $1 (got $2, want $3)"; fail=$((fail+1)); fi; }
code() { curl -s -o /dev/null -w "%{http_code}" "$@"; }
tok() { curl -s -b "$1" -c "$1" "$B$2" | grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | cut -d'"' -f4; }
count() { /c/xampp/mysql/bin/mysql.exe -uroot forum_lab -N -e "SELECT COUNT(*) FROM comments"; }

# missing and malformed discussion ids
check "home page lists discussions" "$(code $B/)" 200
check "missing discussion 404" "$(code $B/discussions/999)" 404
check "malformed id 400" "$(code $B/discussions/abc)" 400

# guest cannot post, even with a direct POST
n=$(count)
check "guest POST redirected to login" "$(code -X POST -d 'body=hi' $B/discussions/1/comments)" 302
check "no row created for guest" "$(count)" "$n"

# register + login
T=$(tok $J /register)
check "register bad csrf" "$(code -b $J -c $J -d "csrf=nope&username=$U&email=$U@example.test&password=password123" $B/register)" 403
check "register ok" "$(code -b $J -c $J -d "csrf=$T&username=$U&email=$U@example.test&password=password123" $B/register)" 303
T=$(tok $J /discussions/1)

# invalid csrf on reply
n=$(count)
check "reply bad csrf" "$(code -b $J -c $J -d 'csrf=bad&body=x' $B/discussions/1/comments/7/replies)" 403
check "no row for bad csrf" "$(count)" "$n"

# depth-5 reply under comment 7 (displayed depth 5) and a second top-level comment
check "depth-5 reply" "$(code -b $J -c $J -d "csrf=$T&body=Deep reply" $B/discussions/1/comments/7/replies)" 303
check "top-level comment" "$(code -b $J -c $J -d "csrf=$T&body=Second top level" $B/discussions/1/comments)" 303
curl -s -b $J $B/discussions/1 | grep -q 'depth 5' && echo "PASS depth 5 shown" || echo "FAIL depth 5 shown"

# cross-discussion parent: make discussion 2 and try to use its comment as parent in discussion 1
/c/xampp/mysql/bin/mysql.exe -uroot forum_lab -e "INSERT IGNORE INTO discussions (id,user_id,title,body) VALUES (2,1,'Other','x'); INSERT IGNORE INTO comments (id,discussion_id,parent_id,user_id,body) VALUES (900,2,NULL,1,'other thread')"
n=$(count)
code -b $J -c $J -d "csrf=$T&body=sneaky" $B/discussions/1/comments/900/replies > /dev/null
check "cross-discussion parent adds no row" "$(count)" "$n"

# script-like comment is escaped
curl -s -b $J -c $J -d "csrf=$T" --data-urlencode "body=<script>alert(1)</script>" $B/discussions/1/comments > /dev/null
curl -s $B/discussions/1 | grep -q '&lt;script&gt;alert(1)&lt;/script&gt;' && echo "PASS script escaped" || echo "FAIL script escaped"
curl -s $B/discussions/1 | grep -q '<script>alert' && echo "FAIL raw script present" || echo "PASS no raw script"

# logout, then guest POST with the old csrf token must not create a row
check "logout" "$(code -b $J -c $J -d "csrf=$T" $B/logout)" 303
n=$(count)
check "post after logout redirected" "$(code -b $J -c $J -d "csrf=$T&body=ghost" $B/discussions/1/comments)" 302
check "no row after logout" "$(count)" "$n"

# wrong password, and a seed user (null hash) can never log in
T=$(tok $G /login)
check "wrong password" "$(code -b $G -c $G -d "csrf=$T&username=$U&password=wrongwrong" $B/login)" 401
check "seed user null hash" "$(code -b $G -c $G -d "csrf=$T&username=Alice&password=" $B/login)" 401

echo "passed $pass failed $fail"
