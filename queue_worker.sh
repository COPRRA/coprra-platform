#!/bin/bash
# Queue Worker Manager for COPRRA

WORKER_PID_FILE="$HOME/queue_worker.pid"
APP_PATH="$HOME/domains/coprra.com/public_html"

case "$1" in
    start)
        if [ -f "$WORKER_PID_FILE" ] && kill -0 $(cat "$WORKER_PID_FILE") 2>/dev/null; then
            echo "⚠️  Queue worker is already running"
            exit 1
        fi
        
        echo "🚀 Starting queue worker..."
        cd "$APP_PATH"
        nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> "$HOME/queue-worker.log" 2>&1 &
        echo $! > "$WORKER_PID_FILE"
        echo "✅ Queue worker started (PID: $(cat $WORKER_PID_FILE))"
        ;;
        
    stop)
        if [ -f "$WORKER_PID_FILE" ]; then
            PID=$(cat "$WORKER_PID_FILE")
            if kill -0 $PID 2>/dev/null; then
                echo "🛑 Stopping queue worker (PID: $PID)..."
                kill $PID
                rm -f "$WORKER_PID_FILE"
                echo "✅ Queue worker stopped"
            else
                echo "⚠️  Worker not running"
                rm -f "$WORKER_PID_FILE"
            fi
        else
            echo "⚠️  No PID file found"
        fi
        ;;
        
    restart)
        $0 stop
        sleep 2
        $0 start
        ;;
        
    status)
        if [ -f "$WORKER_PID_FILE" ] && kill -0 $(cat "$WORKER_PID_FILE") 2>/dev/null; then
            echo "✅ Queue worker is running (PID: $(cat $WORKER_PID_FILE))"
        else
            echo "❌ Queue worker is not running"
        fi
        ;;
        
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
