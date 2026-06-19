                   ┌─────────────┐
                   │   index.php │
                   └──────┬──────┘
                          │
                          ▼
                  ┌──────────────┐
                  │  login.php   │
                  └──────┬───────┘
                         │
            ┌────────────┴────────────┐
            │                         │
            ▼                         ▼
   ┌──────────────┐          ┌────────────────┐
   │ register.php │          │ Đăng nhập OK   │
   └──────┬───────┘          └───────┬────────┘
          │                          │
          └──────────────┬───────────┘
                         ▼
                 ┌──────────────┐
                 │ dashboard.php│
                 └──────┬───────┘
                        │
 ┌──────────────────────┼───────────────────────┐
 │                      │                       │
 ▼                      ▼                       ▼
┌──────────┐    ┌────────────┐        ┌─────────────┐
│profile.php│   │upload.php  │        │documents.php│
└─────┬─────┘   └──────┬─────┘        └──────┬──────┘
      │                │                     │
      ▼                ▼                     ▼
Cập nhật hồ sơ   Upload file          Xem danh sách
                                     tài liệu
                                            │
                              ┌─────────────┼────────────┐
                              │             │            │
                              ▼             ▼            ▼
                         Tìm kiếm      Xem file      Xóa file

Luồng Chatbot AI 

        dashboard.php
               │
               ▼
        chatbot.php
               │
               ▼
      Nhập câu hỏi AI
               │
               ▼
        chat_api.php
               │
               ▼
      Trả kết quả AI


Luồng Upload tài liệu

upload.php
    │
    ▼
Chọn File
(PDF/DOCX/PPTX)
    │
    ▼
upload_document.php
    │
    ▼
Kiểm tra định dạng
    │
    ▼
Lưu uploads/
    │
    ▼
Lưu MySQL
    │
    ▼
documents.php


Luồng đăng nhập

login.php
    │
    ▼
Nhập Email + Password
    │
    ▼
login_process.php
    │
    ├── Sai
    │      │
    │      ▼
    │  login.php
    │
    └── Đúng
           │
           ▼
     dashboard.php

