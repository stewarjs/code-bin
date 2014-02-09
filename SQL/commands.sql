create table users (email varchar(50) primary key, name varchar(20), password varchar(20));
create table jobs (html int, pdf int, startdate date, enddate date, pii int, '508' int, govdelivery int, bulletin int, authorit int, email varchar(50), foreign key(email) references users(email));
