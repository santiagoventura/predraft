#!/bin/bash

echo "🏗️  Setting up MLB Fantasy Draft Helper..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Create Laravel project if it doesn't exist
if [ ! -f "artisan" ]; then
    echo "📦 Creating new Laravel project..."
    composer create-project laravel/laravel:^11.0 temp-laravel
    
    # Move Laravel files to current directory
    mv temp-laravel/* .
    mv temp-laravel/.* . 2>/dev/null || true
    rm -rf temp-laravel
    
    echo "✅ Laravel installed successfully"
else
    echo "✅ Laravel already installed"
fi

# Copy environment file
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✅ Environment file created"
fi

# Start Docker containers
echo "🐳 Starting Docker containers..."
docker-compose up -d

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Install dependencies
echo "📚 Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

echo ""
echo "✨ Setup complete!"
echo ""
echo "🌐 Application is running at: http://localhost:8080"
echo ""
echo "Next steps:"
echo "1. Edit .env and add your GEMINI_API_KEY"
echo "2. Import player data: docker-compose exec app php artisan import:players-csv players.csv"
echo "3. Visit http://localhost:8080 to start using the app"
echo ""

