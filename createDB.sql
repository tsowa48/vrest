create table address(id bigserial primary key not null, name text not null, parent bigint, lon float, lat float);
create table vote(id bigserial primary key not null, name text not null, start bigint not null, stop bigint not null, key text not null, max int not null);
create table rival(id bigserial primary key not null, name text not null, description text, position smallint not null, vid bigint not null);
create table people(id bigserial primary key not null, fio text not null, birth bigint not null, male boolean not null, secret text, aid bigint);
create table va(vid bigint not null, aid bigint not null);
create table result(vid bigint not null, pid bigint not null, bulletin text not null);