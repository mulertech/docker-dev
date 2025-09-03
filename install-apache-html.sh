#!/bin/bash

set -e

PROJECT_NAME=$(basename "$(pwd)")
TEMPLATE_URL="https://api.github.com/repos/mulertech/docker-dev/contents/templates/apache-html"

echo "🚀 Installing Apache HTML Docker environment..."
echo "📁 Project: $PROJECT_NAME"

# Function to download file from GitHub API
download_file() {
    local file_path="$1"
    local output_path="$2"
    
    echo "📥 Downloading $file_path..."
    
    # Get file content from GitHub API
    curl -s "https://api.github.com/repos/mulertech/docker-dev/contents/templates/apache-html/$file_path" \
        | grep '"download_url"' \
        | cut -d '"' -f 4 \
        | xargs curl -s -o "$output_path"
}

# Download template files
download_file "Dockerfile" "Dockerfile"
download_file "compose.yml" "docker-compose.yml"
download_file ".env.example" ".env.example"

# Create .env from .env.example with auto-detected values
if [ -f ".env.example" ]; then
    cp ".env.example" ".env"
    
    # Auto-detect USER_ID and GROUP_ID
    USER_ID=$(id -u)
    GROUP_ID=$(id -g)
    
    # Replace default values with detected ones
    sed -i "s/USER_ID=1000/USER_ID=$USER_ID/g" ".env"
    sed -i "s/GROUP_ID=1000/GROUP_ID=$GROUP_ID/g" ".env"
    
    # Generate unique container name and port based on project
    CONTAINER_NAME="apache-html-$PROJECT_NAME"
    # Generate port based on project name hash (between 8000-9000)
    PORT=$((8000 + $(echo -n "$PROJECT_NAME" | md5sum | cut -c1-3 | sed 's/[a-f]/0/g') % 1000))
    
    sed -i "s/CONTAINER_NAME_APACHE=apache-html/CONTAINER_NAME_APACHE=$CONTAINER_NAME/g" ".env"
    sed -i "s/APACHE_PORT=8080/APACHE_PORT=$PORT/g" ".env"
    sed -i "s|(ex: http://localhost:8080)|http://localhost:$PORT|g" ".env"
    
    echo "✅ Environment configured:"
    echo "   - Container: $CONTAINER_NAME"
    echo "   - Port: $PORT"
fi

# Create a simple index.html if it doesn't exist
if [ ! -f "index.html" ]; then
    cat > index.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apache HTML Docker Environment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 10px;
            text-align: center;
        }
        h1 { margin-bottom: 30px; }
        .commands {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        code {
            background: rgba(255,255,255,0.2);
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Apache HTML Environment Ready!</h1>
        <p>Your Apache server is running and serving HTML/CSS files.</p>
        
        <div class="commands">
            <h3>📋 Quick Commands:</h3>
            <p><strong>Start:</strong> <code>docker compose up -d</code></p>
            <p><strong>Stop:</strong> <code>docker compose down</code></p>
            <p><strong>Logs:</strong> <code>docker compose logs -f</code></p>
        </div>
        
        <p>Edit this <code>index.html</code> file to get started!</p>
    </div>
</body>
</html>
EOF
    echo "📄 Created sample index.html"
fi

echo ""
echo "🎉 Apache HTML environment installed successfully!"
echo ""
echo "📋 Next steps:"
echo "   docker compose up -d"
echo ""
echo "🌐 Your server will be available at: http://localhost:$PORT"