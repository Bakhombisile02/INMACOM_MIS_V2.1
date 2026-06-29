#!/bin/bash
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
PROFILE="/Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/scratch/chrome-profile"
OUT_DIR="/Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/docs-site/public/screenshots"
SCRATCH_DIR="/Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/scratch"

# Helper function to kill hung headless chrome processes locked to our profile
cleanup() {
  echo "Cleaning up headless Chrome processes for this profile..."
  pkill -f "Google Chrome.*chrome-profile"
  sleep 1
}

# Ensure clean state
cleanup

echo "Logging in as Admin..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=5000 --user-data-dir="$PROFILE" --screenshot="$SCRATCH_DIR/login-result.png" "http://localhost:8000/test-login/admin" &
sleep 6
cleanup

echo "Capturing Dashboard..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=10000 --user-data-dir="$PROFILE" --screenshot="$OUT_DIR/dashboard.png" --window-size=1280,800 "http://localhost:8000/dashboard" &
sleep 11
cleanup

echo "Capturing Stations..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=10000 --user-data-dir="$PROFILE" --screenshot="$OUT_DIR/stations.png" --window-size=1280,800 "http://localhost:8000/stations" &
sleep 11
cleanup

echo "Capturing Flow Levels..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=10000 --user-data-dir="$PROFILE" --screenshot="$OUT_DIR/flow_levels.png" --window-size=1280,800 "http://localhost:8000/flow-levels" &
sleep 11
cleanup

echo "Capturing Thresholds..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=10000 --user-data-dir="$PROFILE" --screenshot="$OUT_DIR/thresholds.png" --window-size=1280,800 "http://localhost:8000/thresholds" &
sleep 11
cleanup

echo "Capturing Document Library..."
"$CHROME" --headless=new --disable-gpu --virtual-time-budget=10000 --user-data-dir="$PROFILE" --screenshot="$OUT_DIR/library.png" --window-size=1280,800 "http://localhost:8000/library" &
sleep 11
cleanup

echo "All screenshots captured successfully!"
