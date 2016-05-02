CREATE TABLE `plates`.`student` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NULL,
  `matric_no` VARCHAR(100) NOT NULL,
  `ic_number` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `matric_no_UNIQUE` (`matric_no` ASC),
  UNIQUE INDEX `ic_number_UNIQUE` (`ic_number` ASC));


CREATE TABLE `plates`.`vehicle` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `plate_number` VARCHAR(45) NOT NULL,
  `type` VARCHAR(45) NULL,
  `make` VARCHAR(45) NULL,
  `color` VARCHAR(45) NULL,
  `student_id` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `plate_number_UNIQUE` (`plate_number` ASC),
  INDEX `fk_vehicle_student_idx` (`student_id` ASC),
  CONSTRAINT `fk_vehicle_student`
    FOREIGN KEY (`student_id`)
    REFERENCES `plates`.`student` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
