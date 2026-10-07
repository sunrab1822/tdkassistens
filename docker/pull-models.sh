#!/bin/bash
set -e

echo "[ollama-models] Várakozás az Ollama szerverre..."
until curl -sf http://127.0.0.1:11434/api/tags > /dev/null; do
    sleep 2
done

for model in llama3.1 llava; do
    if ollama list | awk '{print $1}' | grep -q "^${model}:"; then
        echo "[ollama-models] A(z) ${model} modell már letöltve."
    else
        echo "[ollama-models] Letöltés: ${model} (első indításkor ez eltarthat egy ideig)..."
        ollama pull "${model}"
    fi
done

echo "[ollama-models] Minden modell készen áll."
