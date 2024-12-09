# COMP3013 Database Management System

## Course Project Requirements  
### 2024 Fall  

### Introduction  
This is a group project for four to five students.  
This project is to build up a dynamic website for a real-world application. It can be an information system for beauty parlors, kids' training institutes, fitness centers, restaurants, etc. The topic is unlimited. You are free to choose anything you are interested in.  

---

### Requirements  

#### Front End Requirements  
1. Your website needs at least ten pages.  
2. The static pages can be implemented by HTML + CSS, while the dynamic contents can be handled by PHP and Javascript.  
3. Your website may include navigation bars, logo, footers, data validation, etc.  
4. There is no requirement on the open-source platforms. Using them can give you a fancy look to your website, but no extra marks.  
5. Your website needs to be deployed on an Apache server. Use XAMPP.  
6. Your website should allow user registration and login. At least two user types should be offered. For example, suppose you are building a bookstore website, your user types could be:  
   - Owner  
   - Registered user  
   - Anonymous visitor  
7. Your website needs to include at least four features. The more features you have, the higher score you will earn. Feature is a workflow that can allow the user to perform a complete task. For example:  
   - The owner adds new books to the store.  
   - The owner views purchasing data analysis, etc.  
   - The user orders books and leaves the books in the shopping cart.  
   - The user checks out and pays.  
   - The owner, the user, and the visitor search for books.  

#### Back End Requirements  
1. Your ER diagram should have at least 8 entities and 6 relationships.  
2. On average, each table must have no less than 10,000 records. There are at least two tables consisting of more than 50,000 records. (Note: Not all the records have to be real but they should be realistic. You may generate records using a program.)  
3. Your system takes less than 1 second to insert a record into any table.  
4. Your system takes less than 2 seconds to delete or search for a record.  
5. Display the running time of your query on the webpage. You can use the `microtime()` function in PHP.  
6. The logical design of the database must follow the normal forms (BCNF or 3NF).  
7. Use foreign keys. For example, the table `student` has students’ information and the table `registration` has courses registered by each student. If one tuple is removed from `student`, the corresponding tuple(s) will also be removed from `registration`.  

---

### Presentation Requirements  
1. 10-12 minutes for each group.  
2. Make some good slides.  

---

### Documentation Requirements  
1. Briefly introduce the purpose of this project. Define the real-life problem you are solving, address the difficulty of the problem, give the abstraction of the problem, and the major goal of the project.  
2. List all front-end functions that you have implemented.  
3. For database design, make realistic assumptions for modeling if real-life problems do not provide enough information.  
4. Include your final ER diagram and briefly describe each entity and relationship set.  
5. Provide all functional dependencies and schemas.  
6. If your schemas are in normal forms, explain why. Otherwise, decompose them and show the steps in detail.  
7. Describe the primary keys.  
8. Detail the workload of each team member.  

---

### Bonus (1% for each)  
1. Use BLOB to store pictures.  
2. Use triggers to implement constraints other than `NOT NULL`, `PRIMARY KEY`, `UNIQUE`, or referential constraints.  
3. Secure the connection by limiting the database connection’s access based on users' authority.  

---

### Tips  
- For presentation and report:  
  - Follow the top-down procedure. Start with problem definition and assume the audience knows nothing about your project.  
  - Highlight critical points like constraints and triggers.  
  - Avoid making the presentation a function demonstration.  
  - Prepare attractive slides.  

- For web design and implementation: Refer to W3Schools or use provided starter materials.  

---

### Timeline  
1. **Oct. 20**: Grouping on iSpace (2-3 people per team; teams will be paired to form groups of 4-5).  
2. **Nov. 10**: Problem description (5%).  
3. **Nov. 24**: First draft of the ER diagram (5%).  
4. **Dec. 1**: Static webpage implementation (10%).  
5. **Dec. 11-17**: Project presentation (20%).  
6. **Dec. 22**: Final submission, including:  
   - Report  
   - Finalized ER diagram  
   - Webpages  
   - Database (exported as `.sql`).  

---

### Grading  
- Report: 30%  
- Presentation: 20%  
- Website Implementation: 20%  
- Database Implementation: 30%  
- Bonus: 1% for each bonus task completed.  
