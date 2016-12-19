drop view if exists users_summary;

create view users_summary
as
select u.id, u.username, u.password, u.salt, r.name as role, p.name as project
from users u
     left join users_roles_projects urp on u.id=urp.user_id
     left join roles r on r.id=urp.role_id
     left join projects p on p.id=urp.project_id;

go
