| Field            | Rule                        | Lý do                  |
| ---------------- | --------------------------- | ---------------------- |
| current_password | required + đúng mật khẩu cũ | Tránh bị đổi trái phép |
| new_password     | required                    | Bắt buộc               |
| new_password     | min length                  | Tránh mật khẩu yếu     |
| new_password     | confirmed                   | Tránh gõ nhầm          |
| new_password     | khác mật khẩu cũ            | Tăng bảo mật           |
