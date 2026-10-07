import os
import json
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from openai import OpenAI
from duckduckgo_search import DDGS
from database import collection
from document_processor import process_pdf, process_image
import tempfile

app = FastAPI(title="TDK AI Asszisztens API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

OLLAMA_MODEL = "llama3.1"
ollama_client = OpenAI(
    base_url=os.environ.get("OLLAMA_BASE_URL", "http://localhost:11434/v1"),
    api_key="ollama-local"
)

class ChatRequest(BaseModel):
    message: str
    user_id: str

def search_local_documents(query: str, user_id: str):
    print(f"[Vektor Keresés] {user_id} keresése: {query}")
    results = collection.query(
        query_texts=[query], 
        n_results=3, 
        where={"user_id": user_id}, 
        include=['documents', 'metadatas']
    )
    if not results or not results['documents'] or not results['documents'][0]:
        return "Nincs találat a lokális dokumentumokban."
    
    context = ""
    for doc, meta in zip(results['documents'][0], results['metadatas'][0]):
        context += f"Forrás ({meta.get('source')} - {meta.get('page')}. oldal):\n{doc}\n\n"
    return context

def read_full_document(filename: str, user_id: str):
    print(f"[Teljes Dokumentum Olvasás] {user_id} kéri: {filename}")
    results = collection.get(
        where={"$and": [{"source": filename}, {"user_id": user_id}]},
        include=['documents', 'metadatas']
    )
    
    if not results or not results['documents']:
        return f"Nem található '{filename}' nevű dokumentum a rendszerben."
        
    combined = []
    for doc, meta in zip(results['documents'], results['metadatas']):
        combined.append((meta.get('page', 0), doc))
        
    combined.sort(key=lambda x: x[0])
    
    full_text = ""
    for page, text in combined:
        full_text += f"[Oldal {page}]: {text}\n"
        
    if len(full_text) > 15000:
        full_text = full_text[:15000] + "\n\n...[A dokumentum túl hosszú, a vége levágva a memórialimit miatt]"
        
    return full_text

def search_web(query: str):
    print(f"[Web Keresés] Keresés erre: {query}")
    try:
        results = DDGS().text(query, max_results=3)
        if not results:
            return "Nincs értékelhető találat a weben."
        web_context = ""
        for r in results:
            web_context += f"Cím: {r['title']}\nTartalom: {r['body']}\nForrás: {r['href']}\n\n"
        return web_context
    except Exception as e:
        return f"Hiba a webes keresés során: {str(e)}"

tools_definition = [
    {
        "type": "function",
        "function": {
            "name": "search_local_documents",
            "description": "Akkor használd, ha a felhasználó egy konkrét témára, fogalomra, vagy adatra kérdez rá a feltöltött fájljaiban.",
            "parameters": {
                "type": "object",
                "properties": {"query": {"type": "string", "description": "Keresőszó."}},
                "required": ["query"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "read_full_document",
            "description": "KIZÁRÓLAG akkor használd, ha a felhasználó egy megnevezett dokumentum TELJES összefoglalását, vagy a dokumentum átolvasását kéri egyben.",
            "parameters": {
                "type": "object",
                "properties": {"filename": {"type": "string", "description": "A fájl pontos neve kiterjesztéssel, ahogy a listában szerepel (pl. 'minta.pdf')."}},
                "required": ["filename"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "search_web",
            "description": "Akkor használd, ha az interneten kell kutatni friss információk után.",
            "parameters": {
                "type": "object",
                "properties": {"query": {"type": "string", "description": "Keresőkifejezés."}},
                "required": ["query"]
            }
        }
    }
]

SYSTEM_PROMPT = """Te egy okos és precíz TDK és szakdolgozat asszisztens vagy.
SZIGORÚ SZABÁLYOK:
1. Mielőtt megfogalmaznád a végső választ, MINDIG gondold végig a lépéseket egy <gondolkodas> ... </gondolkodas> blokkban! 
2. A gondolkodási blokkban elemezd a használt eszközök (tool-ok) által visszaadott eredményeket. Ha angol nyelvű vizuális leírást kapsz, itt tervezd meg, hogyan fordítod le azt természetes magyar nyelvre tükörfordítások és kitalált szavak nélkül.
3. A <gondolkodas> blokk lezárása után írd le a végleges, tökéletes magyar nyelvű választ.
4. Ha egy teljes fájl összefoglalását kérik, használd a 'read_full_document' eszközt! Ha konkrét dologra kérdeznek, a 'search_local_documents' eszközt használd!
5. Ne hallucinálj! Csak abból dolgozz, amit az eszközök visszaadnak."""

@app.post("/upload")
async def upload_document(file: UploadFile = File(...), user_id: str = Form(...)):
    is_pdf = file.filename.lower().endswith(".pdf")
    is_image = file.filename.lower().endswith((".png", ".jpg", ".jpeg"))
    if not (is_pdf or is_image): return {"error": "Csak PDF és Kép támogatott!"}
    try:
        suffix = ".pdf" if is_pdf else os.path.splitext(file.filename)[1]
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
            content = await file.read()
            tmp.write(content)
            tmp_path = tmp.name
        chunks_inserted = process_pdf(tmp_path, file.filename, user_id) if is_pdf else process_image(tmp_path, file.filename, user_id)
        os.remove(tmp_path)
        return {"message": f"Fájl sikeresen feldolgozva!", "chunk_count": chunks_inserted}
    except Exception as e: return {"error": str(e)}

@app.post("/chat")
def chat_with_agent(request: ChatRequest):
    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": request.message}
    ]
    response = ollama_client.chat.completions.create(model=OLLAMA_MODEL, messages=messages, tools=tools_definition, tool_choice="auto")
    response_message = response.choices[0].message
    
    if response_message.tool_calls:
        messages.append(response_message)
        for tool_call in response_message.tool_calls:
            func_name = tool_call.function.name
            func_args = json.loads(tool_call.function.arguments)
            
            if func_name == "search_local_documents":
                tool_result = search_local_documents(func_args.get("query"), request.user_id)
            elif func_name == "read_full_document":
                tool_result = read_full_document(func_args.get("filename"), request.user_id)
            elif func_name == "search_web":
                tool_result = search_web(func_args.get("query"))
                
            messages.append({"tool_call_id": tool_call.id, "role": "tool", "name": func_name, "content": str(tool_result)})
            
        final_response = ollama_client.chat.completions.create(model=OLLAMA_MODEL, messages=messages)
        return {"response": final_response.choices[0].message.content}
        
    return {"response": response_message.content}

@app.get("/documents/{user_id}")
def list_documents(user_id: str):
    try:
        results = collection.get(where={"user_id": user_id}, include=["metadatas"])
        unique_files = set([meta['source'] for meta in results['metadatas'] if meta and 'source' in meta])
        return {"documents": list(unique_files)}
    except Exception as e: return {"error": str(e)}

@app.delete("/documents/{user_id}/{filename}")
def delete_document(user_id: str, filename: str):
    try:
        collection.delete(where={"$and": [{"source": filename}, {"user_id": user_id}]})
        return {"message": f"A '{filename}' nevű fájl sikeresen törölve."}
    except Exception as e: return {"error": f"Hiba a törlés során: {str(e)}"}

if __name__ == "__main__":
    print("Indítás...")
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)