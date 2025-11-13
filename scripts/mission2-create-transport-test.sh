#!/bin/bash
# Mission 2: Create Standalone Sentry Transport Test

expect << 'EXPECT_EOF'
set timeout 60
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

spawn ssh -p $ssh_port -o StrictHostKeyChecking=no $ssh_user@$ssh_host

expect {
    "password:" {
        send "$ssh_password\r"
        exp_continue
    }
    "Password:" {
        send "$ssh_password\r"
        exp_continue
    }
    -re "\\\$ |# " {
        send "cd $project_path\r"
        expect -re "\\\$ |# "
        
        puts "============================================================"
        puts "MISSION 2: Creating Standalone Sentry Transport Test"
        puts "============================================================"
        puts ""
        
        puts "1. Getting SENTRY_LARAVEL_DSN from .env..."
        send "grep \"SENTRY_LARAVEL_DSN\" .env | cut -d'=' -f2 | tr -d '\"' | tr -d \"'\"\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Creating sentry_transport_test.php..."
        send "cat > public/sentry_transport_test.php << 'SCRIPTEOF'\r"
        expect -re "> "
        send "<?php\r"
        expect -re "> "
        send "require __DIR__.'/../vendor/autoload.php';\r"
        expect -re "> "
        send "\r"
        expect -re "> "
        send "try {\r"
        expect -re "> "
        send "    \\\$dsn = 'https://2c4a83601aa63d57b84bcaac47290c13@o4510335302696960.ingest.de.sentry.io/4510335304859728';\r"
        expect -re "> "
        send "    \r"
        expect -re "> "
        send "    Sentry\\\\init(['dsn' => \\\$dsn, 'traces_sample_rate' => 1.0]);\r"
        expect -re "> "
        send "    \r"
        expect -re "> "
        send "    echo \"Sentry SDK Initialized.<br>\";\r"
        expect -re "> "
        send "    \r"
        expect -re "> "
        send "    \\\$eventId = Sentry\\\\captureMessage('Direct Transport Test from standalone script.');\r"
        expect -re "> "
        send "    \r"
        expect -re "> "
        send "    if (\\\$eventId) {\r"
        expect -re "> "
        send "        echo \"Event captured and sent with ID: \" . \\\$eventId . \"<br>\";\r"
        expect -re "> "
        send "        echo \"Flushing transport queue...<br>\";\r"
        expect -re "> "
        send "        Sentry\\\\flush();\r"
        expect -re "> "
        send "        echo \"Flush complete. Check Sentry.io dashboard now.\";\r"
        expect -re "> "
        send "    } else {\r"
        expect -re "> "
        send "        echo \"Failed to capture event. Something is wrong with the SDK initialization.\";\r"
        expect -re "> "
        send "    }\r"
        expect -re "> "
        send "    \r"
        expect -re "> "
        send "} catch (\\\\Throwable \\\$e) {\r"
        expect -re "> "
        send "    echo \"An exception occurred during the test:<br><pre>\";\r"
        expect -re "> "
        send "    var_dump(\\\$e);\r"
        expect -re "> "
        send "    echo \"</pre>\";\r"
        expect -re "> "
        send "}\r"
        expect -re "> "
        send "SCRIPTEOF\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Verifying file was created..."
        send "ls -la public/sentry_transport_test.php\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Testing script via curl..."
        send "curl -s https://coprra.com/sentry_transport_test.php\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "============================================================"
        puts "✅ Mission 2 Complete"
        puts "============================================================"
        puts ""
        puts "Access the script in browser: https://coprra.com/sentry_transport_test.php"
        
        send "exit\r"
        expect eof
    }
    timeout {
        puts "❌ Connection timeout"
        exit 1
    }
    eof {
        puts "❌ Connection closed"
        exit 1
    }
}
EXPECT_EOF

