#!/bin/bash

# 🚀 Script de Deploy para Render - Barbearia Brum Backend

echo "🚀 Iniciando deploy do backend da Barbearia Brum..."

# Verificar se estamos na pasta correta
if [ ! -f "composer.json" ]; then
    echo "❌ Erro: Execute este script na pasta barbearia-backend/"
    exit 1
fi

# Verificar se Git está inicializado
if [ ! -d ".git" ]; then
    echo "📦 Inicializando repositório Git..."
    git init
    git branch -M main
fi

# Adicionar arquivos
echo "📁 Adicionando arquivos..."
git add .

# Commit
echo "💾 Fazendo commit..."
git commit -m "Backend setup for Render deployment - $(date)"

# Verificar se remote existe
if ! git remote get-url origin > /dev/null 2>&1; then
    echo "🔗 Configure o remote do GitHub:"
    echo "git remote add origin https://github.com/SEU_USUARIO/barbearia-backend.git"
    echo ""
    echo "Depois execute:"
    echo "git push -u origin main"
else
    echo "📤 Fazendo push..."
    git push -u origin main
fi

echo ""
echo "✅ Arquivos preparados para deploy!"
echo ""
echo "📋 Próximos passos:"
echo "1. Acesse: https://render.com"
echo "2. Conecte seu repositório GitHub"
echo "3. Configure como Web Service PHP"
echo "4. Use as configurações do README.md"
echo ""
echo "🌐 URL final será: https://barbearia-brum-backend.onrender.com"