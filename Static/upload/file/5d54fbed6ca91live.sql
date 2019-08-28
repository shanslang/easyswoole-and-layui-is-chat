-- 球队表
create table 'live_team'(
	'id' tinyint unsigned not null auto_increment,
	'name' varchar(20) not null default '', -- 球队名称
	'image' varchar(20) not null default '', -- 不到非常时刻都不要有null值，字符串给默认为'',int给默认为0
	'type' tinyint(1) unsigned not null default 0,
	'create_time' int(10) unsigned not null default 0,
	'update_time' int(10) unsigned not null default 0,
	primary key ('id')
)engine=InnoDB AUTO_INCREMENT=1 default charset=utf8;

-- 直播表
create table 'live_game'(
	'id'  int(10) unsigned not null auto_increment,
	'a_id' tinyint(1) unsigned not null default 0,
	'b_id' tinyint(1) unsigned not null default 0,
	'a_score' int(10) unsigned not null default 0,
	'b_score' int(10) unsigned not null default 0,
	'narrator' varchar(20) not null default '',
	'image' varchar(20) not null default '',
	'start_time' int(10) unsigned not null default 0,
	'create_time' int(10) unsigned not null default 0,
	'update_time' int(10) unsigned not null default 0,
	primary key ('id')
)ENGINE=InnoDB aoto_increment=1 default charset=utf8;


-- 球员表
create table 'live_player'(
	'id' int(10) unsigned not null auto_increment,
	'name' varchar(20) not null default '',
	'image' varchar(20) not null default '',
	'age' tinyint(1) unsigned not null default 0,
	'position' tinyint(1) unsigned not null default 0,
	'status' tinyint(1) unsigned not null default 0,
	'create_time' int(10) unsigned not null default 0,
	'update_time' int(10) unsigned not null default 0,
	primary key ('id')
)ENGINE=InnoDB AUTO_INCREMENT=1 default charset=utf8;

-- 赛事的赛况表
create table 'live_outs' (
	'id' int(10) unsigned  not null auto_increment,
	'game_id' int(10) unsigned not null default 0,
	'team_id' tinyint(1) unsigned not null default 0,
	'content' varchar(200) not null default '',
	'image' varchar(20) not null default '',
	'type' tinyint(1) unsigned not null default 0,
	'status' tinyint(1) unsigned not null default 0,
	'create_time' int(10) unsigned not null default 0,
	primary key ('id')
)ENGINE = InnoDB aoto_increment=1 default charset=utf8;

-- 聊天室表
create table 'live_chart'(
	'id' int(10) unsigned not null auto_increment,
	'game_id' int(10) unsigned not null default 0,
	'user_id' tinyint(1) unsigned not null default 0,
	'content' varchar(200) not null default '',
	'status' tinyint(1) unsigned not null default 0,
	'create_time' int(10) unsigned not null default 0,
	primary key ('id')
)ENGIN=InnoDB auto_increment=1 default charset=utf8;

-- 直播表
create table 'live_game'(
	'id' int(10) unsigned not null auto_increment,
	'a_id' tinyint(1) unsigned not null default 0,
	'b_id' tinyint(1) unsigned not null default 0,
	'a_score' int(10) unsigned not null default 0,
	'b_score' int(10) unsigned not null default 0,
	'narrator' varchar(20) not null default '',
	'image' varchar(20) not null default '',
	'start_time' int(10) not null default 0,
	'create_time' int(10) unsigned not null default 0,
	'update_time' int(10) unsigned not null default 0,
	primary key ('id')
)engin=InnoDB auto_increment=1 default charset=utf8;