ALTER TABLE joueurs ADD show_photo BIT(1);
UPDATE joueurs SET show_photo = 1;
