ALTER TABLE creneau ADD has_time_constraint BIT(1);
UPDATE creneau SET has_time_constraint = 0;
