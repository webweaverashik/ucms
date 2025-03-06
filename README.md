# 🎓 Unique Coaching Management System (UCMS)
*A Complete Solution for Managing Coaching Centers Efficiently*

![UCMS Banner](https://scontent.fdac31-2.fna.fbcdn.net/v/t39.30808-6/480108574_1024583979717464_4542928628394930376_n.jpg?_nc_cat=107&ccb=1-7&_nc_sid=cc71e4&_nc_ohc=VkeSXFqswrgQ7kNvgFMbtM-&_nc_oc=AdjT2pmI_66TEjiMnJvJXxjlhgLihLdA-ejaNHSDiJ_x01X424DJGnA8YIBF8tGeN08&_nc_zt=23&_nc_ht=scontent.fdac31-2.fna&_nc_gid=AZzbO-hGt2VJyRBJa9L5Ibx&oh=00_AYDzOomTdX_5QjjUV2S0Rn85Nm0lyhasTa4w36lzqBoLag&oe=67C7AA49) <!-- Replace with actual banner image URL -->

## 📖 About UCMS
**Unique Coaching Management System (UCMS)** is an **all-in-one coaching center management solution** designed to streamline **student admissions, fee collection, attendance tracking, notes/sheets distribution, teacher scheduling, and role-based access control**.  

UCMS is built using **Laravel (Backend), Bootstrap 5 (Frontend), and Vanilla JS**. It is hosted on **Hostever VPS** and integrates **SSL Wireless SMS Gateway** for automated notifications.  

## 🚀 Features
✅ **Student Management** – Admission, profile management, and activation tracking  
✅ **Guardian Portal** – Guardians can track student progress, payments, and attendance  
✅ **Fee Management** – Automated invoicing, tuition payments, and overdue tracking  
✅ **Sheets Distribution** – Payment-based access to class notes & sheets per topic  
✅ **Attendance Tracking** – Biometric/RFID-based student & teacher attendance system  
✅ **Teacher Management** – Class assignment, salary tracking, and scheduling  
✅ **Multi-Branch Support** – Manage multiple coaching centers from a single platform  
✅ **Role-Based Access Control (RBAC)** – Secure access for different users  
✅ **SMS Notifications** – Integrated **SSL Wireless SMS Gateway** for payment & attendance alerts  
✅ **Comprehensive Reports** – Generate detailed reports on students, teachers, and finances  

## 🛠️ Tech Stack
**Backend:**  
- Laravel (PHP Framework)  
- MySQL (Database)  
- Redis (Caching)  

**Frontend:**  
- Bootstrap 5 (Responsive UI)  
- Vanilla JavaScript  

**Other Tools & Services:**  
- **SSL Wireless** (SMS Gateway)  
- **Hostever VPS** (Hosting)  
- AWS S3 / Local Storage (For storing documents & images)  

## 📂 Database Schema
The UCMS database is **optimized** for **performance and scalability**, covering:  
- **Users & Role Management**  
- **Student & Guardian Management**  
- **Attendance Tracking**  
- **Payments & Invoicing**  
- **Notes & Sheets Distribution**  
- **Teacher & Salary Management**  

📌 **[View Full Database Schema](https://your-schema-url.com)** <!-- Replace with actual link -->

## 🔧 Installation
### 1️⃣ Prerequisites
Before installing UCMS, ensure you have:  
- PHP `>=8.1`  
- MySQL `>=5.7`  
- Composer `>=2.0`  
- Node.js `>=16.0`  

### 2️⃣ Clone the Repository
```bash
git clone https://github.com/webweaverashik/uniquecoachingbd.git
cd uniquecoachingbd
```

### 3️⃣ Install Dependencies
```bash
composer install
npm install
```

### 4️⃣ Configure the .env File
```bash
cp .env.example .env
php artisan key:generate
```

### 5️⃣ Run Database Migrations
```bash
php artisan migrate --seed
```

### 6️⃣ Start the Development Server
```bash
php artisan serve
npm run dev
```

Set up your database connection in `.env`
```bash
DB_DATABASE=ucms_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

Now, you can access UCMS at `http://localhost:8000`.


## 📜 License
**This project is licensed under the MIT License.** <br>
📌 **[Read Full License](https://opensource.org/licenses/MIT)**



## 📞 Support & Contact
For any queries, feature requests, or issues, feel free to reach out:
- 📧 Email: `support@uniquecoachingbd.com`
- 🌐 Website: `www.uniquecoachingbd.com`
- 🐛 Report Issues: GitHub Issues
- 🌟 Like This Project? Give It a Star ⭐ on GitHub!
