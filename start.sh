#!/bin/bash
# Quick Setup Script for IFM Peer Tutoring System (Linux)

echo "IFM Peer Tutoring System - Quick Setup"
echo "=========================================="
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed"
    echo "Installing PHP..."
    sudo apt update
    sudo apt install -y php php-cli php-sqlite3 sqlite3
else
    echo "PHP is installed: $(php -v | head -n 1)"
fi

# Check if SQLite is installed
if ! command -v sqlite3 &> /dev/null; then
    echo "SQLite is not installed"
    echo "Installing SQLite..."
    sudo apt install -y sqlite3
else
    echo "SQLite is installed: $(sqlite3 --version)"
fi

echo ""
echo "Setting up database..."

# Set permissions
chmod 666 peer_tutoring.db 2>/dev/null || echo "Database file not found (will be created on first run)"
chmod -R 777 uploads/profile_pics/ 2>/dev/null || mkdir -p uploads/profile_pics && chmod 777 uploads/profile_pics

echo "Permissions set"
echo ""
echo "Starting PHP Development Server..."
echo ""
echo "Access the application at: http://localhost:8000"
echo "Login page: http://localhost:8000/auth/login.php"
echo "Register page: http://localhost:8000/auth/register.php"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""
echo "=========================================="
echo ""

# Start PHP server
php -S localhost:8000
