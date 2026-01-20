(module
 (type $0 (func (param i32 i32) (result i32)))
 (type $1 (func))
 (type $2 (func (result i32)))
 (type $3 (func (param i32) (result i32)))
 (type $4 (func (param i32)))
 (type $5 (func (param i32 i32 i32 i32)))
 (import "index" "log" (func $assembly/index/log (param i32)))
 (import "index" "get_auth_token" (func $assembly/index/get_auth_token (result i32)))
 (import "env" "abort" (func $~lib/builtins/abort (param i32 i32 i32 i32)))
 (global $~lib/rt/stub/offset (mut i32) (i32.const 0))
 (memory $0 1)
 (data $0 (i32.const 1036) "L")
 (data $0.1 (i32.const 1048) "\02\00\00\00<\00\00\00O\00c\00t\00a\00r\00i\00n\00e\00 \00D\00a\00s\00h\00b\00o\00a\00r\00d\00 \00i\00n\00i\00t\00i\00a\00l\00i\00z\00e\00d")
 (data $1 (i32.const 1116) "\ec")
 (data $1.1 (i32.const 1128) "\02\00\00\00\ce\00\00\00{\00\n\00 \00 \00 \00 \00\"\00u\00s\00e\00r\00s\00\"\00:\00 \001\002\005\000\00,\00\n\00 \00 \00 \00 \00\"\00r\00e\00v\00e\00n\00u\00e\00\"\00:\00 \004\005\006\007\008\00.\009\000\00,\00\n\00 \00 \00 \00 \00\"\00a\00c\00t\00i\00v\00e\00D\00e\00s\00k\00t\00o\00p\00s\00\"\00:\00 \003\004\002\00,\00\n\00 \00 \00 \00 \00\"\00s\00y\00s\00t\00e\00m\00H\00e\00a\00l\00t\00h\00\"\00:\00 \00\"\00g\00o\00o\00d\00\"\00\n\00 \00 \00}")
 (data $2 (i32.const 1356) ",\01")
 (data $2.1 (i32.const 1368) "\02\00\00\00\10\01\00\00{\00\n\00 \00 \00 \00 \00\"\00u\00s\00e\00r\00s\00\"\00:\00 \00[\00\n\00 \00 \00 \00 \00 \00 \00{\00\"\00i\00d\00\"\00:\00 \001\00,\00 \00\"\00n\00a\00m\00e\00\"\00:\00 \00\"\00A\00d\00m\00i\00n\00 \00U\00s\00e\00r\00\"\00,\00 \00\"\00r\00o\00l\00e\00\"\00:\00 \00\"\00a\00d\00m\00i\00n\00\"\00}\00,\00\n\00 \00 \00 \00 \00 \00 \00{\00\"\00i\00d\00\"\00:\00 \002\00,\00 \00\"\00n\00a\00m\00e\00\"\00:\00 \00\"\00C\00u\00s\00t\00o\00m\00e\00r\00 \001\00\"\00,\00 \00\"\00r\00o\00l\00e\00\"\00:\00 \00\"\00u\00s\00e\00r\00\"\00}\00\n\00 \00 \00 \00 \00]\00\n\00 \00 \00}")
 (data $3 (i32.const 1660) "\bc")
 (data $3.1 (i32.const 1672) "\02\00\00\00\a0\00\00\00{\00\n\00 \00 \00 \00 \00\"\00p\00a\00g\00e\00V\00i\00e\00w\00s\00\"\00:\00 \001\005\004\002\000\00,\00\n\00 \00 \00 \00 \00\"\00u\00n\00i\00q\00u\00e\00V\00i\00s\00i\00t\00o\00r\00s\00\"\00:\00 \003\002\001\000\00,\00\n\00 \00 \00 \00 \00\"\00b\00o\00u\00n\00c\00e\00R\00a\00t\00e\00\"\00:\00 \004\002\00.\005\00\n\00 \00 \00}")
 (data $4 (i32.const 1852) "\1c")
 (data $4.1 (i32.const 1864) "\02\00\00\00\04\00\00\00{\00}")
 (data $5 (i32.const 1884) ",")
 (data $5.1 (i32.const 1896) "\02\00\00\00\10\00\00\00A\00c\00t\00i\00o\00n\00:\00 ")
 (data $6 (i32.const 1932) "\1c")
 (data $6.1 (i32.const 1944) "\02")
 (data $7 (i32.const 1964) "<")
 (data $7.1 (i32.const 1976) "\02\00\00\00(\00\00\00A\00l\00l\00o\00c\00a\00t\00i\00o\00n\00 \00t\00o\00o\00 \00l\00a\00r\00g\00e")
 (data $8 (i32.const 2028) "<")
 (data $8.1 (i32.const 2040) "\02\00\00\00\1e\00\00\00~\00l\00i\00b\00/\00r\00t\00/\00s\00t\00u\00b\00.\00t\00s")
 (data $9 (i32.const 2092) ",")
 (data $9.1 (i32.const 2104) "\02\00\00\00\16\00\00\00c\00r\00e\00a\00t\00e\00_\00u\00s\00e\00r")
 (data $10 (i32.const 2140) "<")
 (data $10.1 (i32.const 2152) "\02\00\00\00\1e\00\00\00C\00r\00e\00a\00t\00i\00n\00g\00 \00u\00s\00e\00r\00:\00 ")
 (data $11 (i32.const 2204) "<")
 (data $11.1 (i32.const 2216) "\02\00\00\00\1e\00\00\00u\00p\00d\00a\00t\00e\00_\00s\00e\00t\00t\00i\00n\00g\00s")
 (data $12 (i32.const 2268) "<")
 (data $12.1 (i32.const 2280) "\02\00\00\00&\00\00\00U\00p\00d\00a\00t\00i\00n\00g\00 \00s\00e\00t\00t\00i\00n\00g\00s\00:\00 ")
 (export "init" (func $assembly/index/init))
 (export "getDashboardStats" (func $assembly/index/getDashboardStats))
 (export "renderComponent" (func $assembly/index/renderComponent))
 (export "handleAction" (func $assembly/index/handleAction))
 (export "memory" (memory $0))
 (start $~start)
 (func $assembly/index/init
  i32.const 1056
  call $assembly/index/log
 )
 (func $assembly/index/getDashboardStats (result i32)
  call $assembly/index/get_auth_token
  drop
  i32.const 1136
 )
 (func $assembly/index/renderComponent (param $0 i32) (result i32)
  block $case3|0
   block $case2|0
    block $case1|0
     block $case0|0
      local.get $0
      i32.const 1
      i32.sub
      br_table $case0|0 $case1|0 $case2|0 $case3|0
     end
     call $assembly/index/getDashboardStats
     drop
     i32.const 1136
     return
    end
    i32.const 1376
    return
   end
   i32.const 1680
   return
  end
  i32.const 1872
 )
 (func $~lib/string/String#get:length (param $0 i32) (result i32)
  local.get $0
  i32.const 20
  i32.sub
  i32.load offset=16
  i32.const 1
  i32.shr_u
 )
 (func $~lib/string/String.__concat (param $0 i32) (param $1 i32) (result i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  (local $5 i32)
  (local $6 i32)
  (local $7 i32)
  (local $8 i32)
  (local $9 i32)
  (local $10 i32)
  local.get $0
  call $~lib/string/String#get:length
  i32.const 1
  i32.shl
  local.tee $2
  local.get $1
  call $~lib/string/String#get:length
  i32.const 1
  i32.shl
  local.tee $3
  i32.add
  local.tee $4
  if (result i32)
   local.get $4
   i32.const 1073741804
   i32.gt_u
   if
    i32.const 1984
    i32.const 2048
    i32.const 86
    i32.const 30
    call $~lib/builtins/abort
    unreachable
   end
   local.get $4
   i32.const 16
   i32.add
   local.tee $5
   i32.const 1073741820
   i32.gt_u
   if
    i32.const 1984
    i32.const 2048
    i32.const 33
    i32.const 29
    call $~lib/builtins/abort
    unreachable
   end
   global.get $~lib/rt/stub/offset
   global.get $~lib/rt/stub/offset
   i32.const 4
   i32.add
   local.tee $8
   local.get $5
   i32.const 19
   i32.add
   i32.const -16
   i32.and
   i32.const 4
   i32.sub
   local.tee $9
   i32.add
   local.tee $5
   memory.size
   local.tee $6
   i32.const 16
   i32.shl
   i32.const 15
   i32.add
   i32.const -16
   i32.and
   local.tee $10
   i32.gt_u
   if
    local.get $6
    local.get $5
    local.get $10
    i32.sub
    i32.const 65535
    i32.add
    i32.const -65536
    i32.and
    i32.const 16
    i32.shr_u
    local.tee $10
    local.get $6
    local.get $10
    i32.gt_s
    select
    memory.grow
    i32.const 0
    i32.lt_s
    if
     local.get $10
     memory.grow
     i32.const 0
     i32.lt_s
     if
      unreachable
     end
    end
   end
   local.get $5
   global.set $~lib/rt/stub/offset
   local.get $9
   i32.store
   local.get $8
   i32.const 4
   i32.sub
   local.tee $5
   i32.const 0
   i32.store offset=4
   local.get $5
   i32.const 0
   i32.store offset=8
   local.get $5
   i32.const 2
   i32.store offset=12
   local.get $5
   local.get $4
   i32.store offset=16
   local.get $8
   i32.const 16
   i32.add
   local.tee $4
   local.get $0
   local.get $2
   memory.copy
   local.get $2
   local.get $4
   i32.add
   local.get $1
   local.get $3
   memory.copy
   local.get $4
  else
   i32.const 1952
  end
 )
 (func $~lib/string/String.__eq (param $0 i32) (param $1 i32) (result i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  (local $5 i32)
  local.get $0
  local.get $1
  i32.eq
  if
   i32.const 1
   return
  end
  local.get $1
  i32.eqz
  local.get $0
  i32.eqz
  i32.or
  if
   i32.const 0
   return
  end
  local.get $0
  call $~lib/string/String#get:length
  local.set $2
  local.get $1
  call $~lib/string/String#get:length
  local.get $2
  i32.ne
  if
   i32.const 0
   return
  end
  block $__inlined_func$~lib/util/string/compareImpl$10
   loop $while-continue|0
    local.get $2
    local.tee $3
    i32.const 1
    i32.sub
    local.set $2
    local.get $3
    if
     local.get $0
     i32.load16_u
     local.tee $5
     local.get $1
     i32.load16_u
     local.tee $3
     i32.sub
     local.set $4
     local.get $3
     local.get $5
     i32.ne
     br_if $__inlined_func$~lib/util/string/compareImpl$10
     local.get $0
     i32.const 2
     i32.add
     local.set $0
     local.get $1
     i32.const 2
     i32.add
     local.set $1
     br $while-continue|0
    end
   end
   i32.const 0
   local.set $4
  end
  local.get $4
  i32.eqz
 )
 (func $assembly/index/handleAction (param $0 i32) (param $1 i32) (result i32)
  i32.const 1904
  local.get $0
  call $~lib/string/String.__concat
  call $assembly/index/log
  local.get $0
  i32.const 2112
  call $~lib/string/String.__eq
  if
   i32.const 2160
   local.get $1
   call $~lib/string/String.__concat
   call $assembly/index/log
   i32.const 1
   return
  else
   local.get $0
   i32.const 2224
   call $~lib/string/String.__eq
   if
    i32.const 2288
    local.get $1
    call $~lib/string/String.__concat
    call $assembly/index/log
    i32.const 1
    return
   end
  end
  i32.const 0
 )
 (func $~start
  i32.const 2332
  global.set $~lib/rt/stub/offset
 )
)
