#!/bin/bash
# Mission 2: Create Standalone Sentry Transport Test (Fixed Version)

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
        
        puts "1. Creating sentry_transport_test.php using PHP heredoc..."
        send "php -r \"file_put_contents('public/sentry_transport_test.php', '<?php\nrequire __DIR__.\"/../vendor/autoload.php\";\n\ntry {\n    \\\$dsn = \\\"https://2c4a83601aa63d57b84bcaac47290c13@o4510335302696960.ingest.de.sentry.io/4510335304859728\\\";\n    \n    Sentry\\\\init([\\\"dsn\\\" => \\\$dsn, \\\"traces_sample_rate\\\" => 1.0]);\n    \n    echo \\\"Sentry SDK Initialized.<br>\\\\n\\\";\n    \n    \\\$eventId = Sentry\\\\captureMessage(\\\"Direct Transport Test from standalone script.\\\");\n    \n    if (\\\$eventId) {\n        echo \\\"Event captured and sent with ID: \\\" . \\\$eventId . \\\"<br>\\\\n\\\";\n        echo \\\"Flushing transport queue...<br>\\\\n\\\";\n        Sentry\\\\flush();\n        echo \\\"Flush complete. Check Sentry.io dashboard now.\\\";\n    } else {\n        echo \\\"Failed to capture event. Something is wrong with the SDK initialization.\\\";\n    }\n    \n} catch (\\\\Throwable \\\$e) {\n    echo \\\"An exception occurred during the test:<br><pre>\\\\n\\\";\n    var_dump(\\\$e);\n    echo \\\"</pre>\\\";\n}\n');\"\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Verifying file was created..."
        send "ls -la public/sentry_transport_test.php\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing script via curl..."
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

