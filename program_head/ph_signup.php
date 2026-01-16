<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f4f6f9;
    }
    .logo{
        width: 240px;
        height: 50px;
    }
    .form-container {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 400px;
        text-align: center;
    }
    .form-container h2 {
        color: #333;
        margin-bottom: 10px;
    }
    .form-container label {
        display: block;
        font-weight: bold;
        color: #555;
        text-align: left;
        margin-bottom: 5px;
    }
    .form-container input[type="text"],
    .form-container input[type="password"],
    .form-container select {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-container select {
        appearance: none;
        background: #f9f9f9;
        cursor: pointer;
    }
    .form-container input[type="submit"] {
        margin-top: 10px;
        width: 100%;
        padding: 12px;
        background-color: #B33838;
        color: white;
        font-weight: bold;
        font-size: 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .form-container input[type="submit"]:hover {
        background-color: maroon;
    }
</style>
    <script>
        function redirectToPage(event) {
            event.preventDefault();
            var organization = document.getElementById("department").value;
            
            if (organization === "Department of Technical Programs") {
                window.location.href = "ph_home.php";
            } else {
                event.target.submit();
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <img src="/PH_DN_VP_Dashboard/greetings/umdc-logo.png" alt="Logo" class="logo">
        <h2>Sign Up</h2>
        <form action="ph_signup_process.php" method="POST">

            <label for="id_no">ID No.:</label>
            <input type="text" name="id_no" id="id_no" required>
            
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>

            <label for="department">Department:</label>
            <select name="department" id="department" required>
                <option value="">Select Department</option>
                <option value="Department of Accounting Education">Department of Accounting Education</option>
                <option value="Department of Art and Sciences">Department of Art and Sciences</option>
                <option value="Department of Business Administration">Department of Business Administration</option>
                <option value="Department of Criminal Justice Education">Department of Criminal Justice Education</option>
                <option value="Department of Teachers Education">Department of Teachers Education</option>
                <option value="Department of Technical Programs">Department of Technical Programs</option>
                <option value="Senior High School">Senior High School</option>
            </select>

            <label for="course">Course:</label>
            <select name="course" id="course" required>
                <option value="">Select Course</option>
            </select>
            
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            
            <input type="submit" value="Sign Up">
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const departmentCourses = {
                "Department of Technical Programs": [
                    "BS IN INFORMATION TECHNOLOGY",
                    "BS IN COMPUTER ENGINEERING"
                ],
                "Department of Accounting Education": [
                    "BS IN ACCOUNTANCY",
                    "BS IN INTERNAL AUDITING",
                    "BS IN MANAGEMENT ACCOUNTING"
                ],
                "Department of Art and Sciences": [
                    "BS IN PSYCHOLOGY",
                    "BS IN SOCIAL WORK",
                    "BA IN POLITICAL SCIENCE",
                    "BA IN COMMUNICATION"
                ],
                "Department of Business Administration": [
                    "BS IN BUSINESS ADMINISTRATION - FINANCIAL MANAGEMENT",
                    "BS IN BUSINESS ADMINISTRATION - HUMAN RESOURCE MANAGEMENT",
                    "BS IN BUSINESS ADMINISTRATION - MARKETING MANAGEMENT"
                ],
                "Department of Hospitality Education": [
                    "BS IN TOURISM MANAGEMENT"
                ],
                "Department of Criminal Justice Education": [
                    "BS IN CRIMINOLOGY"
                ],
                "Department of Teachers Education":[
                    "BACHELOR IN ELEMENTARY EDUCATION",
                    "BACHELOR IN SPECIAL NEEDS EDUCATION MAJOR IN ELEMENTARY SCHOOL TEACHING",
                    "BACHELOR OF PHYSICAL EDUCATION",
                    "BACHELOR OF SECONDARY EDUCATION - SCIENCE",
                    "BACHELOR OF SECONDARY EDUCATION - ENGLISH",
                    "BACHELOR OF SECONDARY EDUCATION - FILIPINO",
                    "BACHELOR OF SECONDARY EDUCATION - SOCIAL STUDIES",
                    "BACHELOR OF SECONDARY EDUCATION - MATHEMATICS",
                    "BACHELOR OF TECHNICAL VOCATIONAL TEACHER EDUCATION - FOOD SERVICE",
                    "BACHELOR OF TECHNICAL VOCATIONAL TEACHER EDUCATION - AUTOMOTIVE TECHNOLOGY"
                ]
            };
            document.getElementById("department").addEventListener("change", function() {
                const dept = this.value;
                const courseDropdown = document.getElementById("course");

                courseDropdown.innerHTML = '<option value="">Select Course</option>';

                if (departmentCourses[dept]) {
                    departmentCourses[dept].forEach(course => {
                        let opt = document.createElement("option");
                        opt.value = course;
                        opt.textContent = course;
                        courseDropdown.appendChild(opt);
                    });
                }
            });
        });
    </script>
</body>
</html>
