import os
import chromadb
from chromadb.utils import embedding_functions

CHROMA_DATA_DIR = os.environ.get("CHROMA_DATA_DIR", "./chroma_data")
os.makedirs(CHROMA_DATA_DIR, exist_ok=True)

local_ef = embedding_functions.SentenceTransformerEmbeddingFunction(
    model_name="paraphrase-multilingual-MiniLM-L12-v2"
)

client = chromadb.PersistentClient(path=CHROMA_DATA_DIR)
collection = client.get_or_create_collection(
    name="tdk-tudasbazis", 
    embedding_function=local_ef
)