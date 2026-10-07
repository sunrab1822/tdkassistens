import os
import fitz
import pytesseract
from PIL import Image
import io
import base64
from openai import OpenAI
from langchain_text_splitters import RecursiveCharacterTextSplitter
from database import collection

pytesseract.pytesseract.tesseract_cmd = os.environ.get(
    "TESSERACT_CMD", r'C:\Program Files\Tesseract-OCR\tesseract.exe'
)

ollama_client = OpenAI(
    base_url=os.environ.get("OLLAMA_BASE_URL", "http://localhost:11434/v1"),
    api_key="ollama-local"
)

text_splitter = RecursiveCharacterTextSplitter.from_tiktoken_encoder(
    encoding_name="cl100k_base", chunk_size=500, chunk_overlap=100
)

def analyze_image_layout(image_bytes):
    base64_image = base64.b64encode(image_bytes).decode('utf-8')
    try:
        response = ollama_client.chat.completions.create(
            model="llava",
            messages=[
                {
                    "role": "user",
                    "content": [
                        {"type": "text", "text": "Describe the layout, visual elements, and structure of this image briefly in English. Do not try to read small text."},
                        {"type": "image_url", "image_url": {"url": f"data:image/jpeg;base64,{base64_image}"}}
                    ]
                }
            ],
            max_tokens=150,
            temperature=0.1
        )
        return response.choices[0].message.content
    except Exception as e:
        print(f"LLaVA hiba: {e}")
        return ""

def process_pdf(file_path, filename, user_id):
    doc = fitz.open(file_path)
    all_batch_ids, all_batch_docs, all_batch_metas = [], [], []
    
    for page_num in range(len(doc)):
        page = doc.load_page(page_num)
        page_text = page.get_text("text").strip()
        
        image_info_list = page.get_image_info(xrefs=True)
        ai_desc_parts = []
        
        if image_info_list:
            for img_index, img_info in enumerate(image_info_list):
                rect = fitz.Rect(img_info["bbox"])
                if rect.width < 50 or rect.height < 50:
                    continue
                
                pix = page.get_pixmap(dpi=200, clip=rect)
                img_bytes = pix.tobytes("jpeg")
                pil_image = Image.open(io.BytesIO(img_bytes))
                
                ocr_text = pytesseract.image_to_string(pil_image, lang='hun').strip()
                if ocr_text:
                    ai_desc_parts.append(f"[Ábra {img_index+1} Kinyert Szövege]:\n{ocr_text}")
                
                visual_desc = analyze_image_layout(img_bytes)
                if visual_desc:
                    ai_desc_parts.append(f"[Ábra {img_index+1} Vizuális Leírása]:\n{visual_desc}")

        combined_text = page_text
        if ai_desc_parts:
            combined_text += "\n\n" + "\n\n".join(ai_desc_parts)
            
        if not combined_text.strip():
            continue
            
        chunks = text_splitter.split_text(combined_text)
        
        for i, chunk in enumerate(chunks):
            doc_id = f"{user_id}_{filename}_p{page_num}_c{i}"
            all_batch_ids.append(doc_id)
            all_batch_docs.append(chunk)
            all_batch_metas.append({
                "source": filename,
                "page": page_num + 1,
                "user_id": user_id
            })
            
    doc.close()
    
    if all_batch_ids:
        collection.upsert(ids=all_batch_ids, documents=all_batch_docs, metadatas=all_batch_metas)
    return len(all_batch_ids)

def process_image(file_path, filename, user_id):
    pil_image = Image.open(file_path)
    with open(file_path, "rb") as f:
        img_bytes = f.read()
        
    ai_desc_parts = []
    
    ocr_text = pytesseract.image_to_string(pil_image, lang='hun').strip()
    if ocr_text:
        ai_desc_parts.append(f"[Kép Kinyert Szövege]:\n{ocr_text}")
        
    visual_desc = analyze_image_layout(img_bytes)
    if visual_desc:
        ai_desc_parts.append(f"[Kép Vizuális Leírása]:\n{visual_desc}")
        
    combined_text = "\n\n".join(ai_desc_parts)
    if not combined_text.strip():
        return 0
        
    chunks = text_splitter.split_text(combined_text)
    all_batch_ids, all_batch_docs, all_batch_metas = [], [], []
    
    for i, chunk in enumerate(chunks):
        doc_id = f"{user_id}_{filename}_img_c{i}"
        all_batch_ids.append(doc_id)
        all_batch_docs.append(chunk)
        all_batch_metas.append({
            "source": filename,
            "page": 1,
            "user_id": user_id
        })
        
    if all_batch_ids:
        collection.upsert(ids=all_batch_ids, documents=all_batch_docs, metadatas=all_batch_metas)
    return len(all_batch_ids)