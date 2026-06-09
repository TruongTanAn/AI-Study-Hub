# API CONTRACT

## Authentication

### POST /backend/register_process.php

Request

{
"full_name": "Nguyen Van A",
"email": "[a@gmail.com](mailto:a@gmail.com)",
"password": "123456"
}

Response

{
"success": true,
"message": "Register success"
}

---

### POST /backend/login_process.php

Request

{
"email": "[a@gmail.com](mailto:a@gmail.com)",
"password": "123456"
}

Response

{
"success": true,
"user_id": 1,
"role": "user"
}

---

## Documents

### POST /backend/upload_document.php

FormData

title
description
category_id
file

Response

{
"success": true,
"document_id": 1
}

---

### GET /backend/search_document.php?q=java

Response

[
{
"document_id": 1,
"title": "Java Basic",
"file_type": "pdf"
}
]

---

### POST /backend/delete_document.php

Request

{
"document_id": 1
}

Response

{
"success": true
}

---

## Chatbot

### POST /backend/chat_api.php

Request

{
"message": "What is OOP?"
}

Response

{
"answer": "Object-Oriented Programming..."
}
