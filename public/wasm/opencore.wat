(module
 (type $0 (func (param i32) (result i32)))
 (type $1 (func (param i32 i32) (result i32)))
 (type $2 (func))
 (type $3 (func (param i32)))
 (type $4 (func (param i32 i32 i32 i32)))
 (type $5 (func (param i32 i32)))
 (type $6 (func (result i32)))
 (import "index" "log" (func $assembly/index/log (param i32)))
 (import "env" "abort" (func $~lib/builtins/abort (param i32 i32 i32 i32)))
 (global $~lib/rt/stub/offset (mut i32) (i32.const 0))
 (memory $0 1)
 (data $0 (i32.const 1036) "\\")
 (data $0.1 (i32.const 1048) "\02\00\00\00F\00\00\00O\00p\00e\00n\00 \00C\00o\00r\00e\00 \00S\00a\00a\00S\00 \00P\00l\00a\00t\00f\00o\00r\00m\00 \00i\00n\00i\00t\00i\00a\00l\00i\00z\00e\00d")
 (data $1 (i32.const 1132) ",")
 (data $1.1 (i32.const 1144) "\02\00\00\00\18\00\00\00{\00\n\00 \00 \00 \00 \00\"\00i\00d\00\"\00:\00 ")
 (data $2 (i32.const 1180) "\9c\01")
 (data $2.1 (i32.const 1192) "\02\00\00\00\84\01\00\00,\00\n\00 \00 \00 \00 \00\"\00n\00a\00m\00e\00\"\00:\00 \00\"\00E\00n\00t\00e\00r\00p\00r\00i\00s\00e\00 \00C\00o\00r\00p\00\"\00,\00\n\00 \00 \00 \00 \00\"\00e\00m\00a\00i\00l\00\"\00:\00 \00\"\00c\00o\00n\00t\00a\00c\00t\00@\00e\00n\00t\00e\00r\00p\00r\00i\00s\00e\00.\00c\00o\00m\00\"\00,\00\n\00 \00 \00 \00 \00\"\00p\00l\00a\00n\00\"\00:\00 \00\"\00e\00n\00t\00e\00r\00p\00r\00i\00s\00e\00\"\00,\00\n\00 \00 \00 \00 \00\"\00d\00e\00s\00k\00t\00o\00p\00s\00\"\00:\00 \005\000\00,\00\n\00 \00 \00 \00 \00\"\00b\00i\00l\00l\00i\00n\00g\00\"\00:\00 \00{\00\n\00 \00 \00 \00 \00 \00 \00\"\00a\00m\00o\00u\00n\00t\00\"\00:\00 \002\005\000\000\00.\000\000\00,\00\n\00 \00 \00 \00 \00 \00 \00\"\00s\00t\00a\00t\00u\00s\00\"\00:\00 \00\"\00a\00c\00t\00i\00v\00e\00\"\00\n\00 \00 \00 \00 \00}\00\n\00 \00 \00}")
 (data $3 (i32.const 1596) "\1c\00\00\00\03\00\00\00\00\00\00\00\04\00\00\00\0c\00\00\00\80\04\00\00\00\00\00\00\b0\04")
 (data $4 (i32.const 1628) "|")
 (data $4.1 (i32.const 1640) "\02\00\00\00d\00\00\00t\00o\00S\00t\00r\00i\00n\00g\00(\00)\00 \00r\00a\00d\00i\00x\00 \00a\00r\00g\00u\00m\00e\00n\00t\00 \00m\00u\00s\00t\00 \00b\00e\00 \00b\00e\00t\00w\00e\00e\00n\00 \002\00 \00a\00n\00d\00 \003\006")
 (data $5 (i32.const 1756) "<")
 (data $5.1 (i32.const 1768) "\02\00\00\00&\00\00\00~\00l\00i\00b\00/\00u\00t\00i\00l\00/\00n\00u\00m\00b\00e\00r\00.\00t\00s")
 (data $6 (i32.const 1820) "\1c")
 (data $6.1 (i32.const 1832) "\02\00\00\00\02\00\00\000")
 (data $7 (i32.const 1852) "<")
 (data $7.1 (i32.const 1864) "\02\00\00\00(\00\00\00A\00l\00l\00o\00c\00a\00t\00i\00o\00n\00 \00t\00o\00o\00 \00l\00a\00r\00g\00e")
 (data $8 (i32.const 1916) "<")
 (data $8.1 (i32.const 1928) "\02\00\00\00\1e\00\00\00~\00l\00i\00b\00/\00r\00t\00/\00s\00t\00u\00b\00.\00t\00s")
 (data $9 (i32.const 1980) "\\")
 (data $9.1 (i32.const 1992) "\02\00\00\00H\00\00\000\001\002\003\004\005\006\007\008\009\00a\00b\00c\00d\00e\00f\00g\00h\00i\00j\00k\00l\00m\00n\00o\00p\00q\00r\00s\00t\00u\00v\00w\00x\00y\00z")
 (data $10 (i32.const 2076) "\1c")
 (data $10.1 (i32.const 2088) "\02")
 (data $11 (i32.const 2108) "\\")
 (data $11.1 (i32.const 2120) "\02\00\00\00>\00\00\00C\00r\00e\00a\00t\00i\00n\00g\00 \00d\00e\00s\00k\00t\00o\00p\00 \00f\00o\00r\00 \00c\00u\00s\00t\00o\00m\00e\00r\00:\00 ")
 (data $12 (i32.const 2204) ",")
 (data $12.1 (i32.const 2216) "\02\00\00\00\0e\00\00\00S\00p\00e\00c\00s\00:\00 ")
 (data $13 (i32.const 2252) "<")
 (data $13.1 (i32.const 2264) "\02\00\00\00(\00\00\00{\00\n\00 \00 \00 \00 \00\"\00c\00u\00s\00t\00o\00m\00e\00r\00I\00d\00\"\00:\00 ")
 (data $14 (i32.const 2316) "\ac\01")
 (data $14.1 (i32.const 2328) "\02\00\00\00\9c\01\00\00,\00\n\00 \00 \00 \00 \00\"\00c\00u\00r\00r\00e\00n\00t\00P\00e\00r\00i\00o\00d\00\"\00:\00 \00{\00\n\00 \00 \00 \00 \00 \00 \00\"\00s\00t\00a\00r\00t\00\"\00:\00 \00\"\002\000\002\006\00-\000\001\00-\000\001\00\"\00,\00\n\00 \00 \00 \00 \00 \00 \00\"\00e\00n\00d\00\"\00:\00 \00\"\002\000\002\006\00-\000\001\00-\003\001\00\"\00,\00\n\00 \00 \00 \00 \00 \00 \00\"\00a\00m\00o\00u\00n\00t\00\"\00:\00 \002\005\000\000\00.\000\000\00\n\00 \00 \00 \00 \00}\00,\00\n\00 \00 \00 \00 \00\"\00u\00s\00a\00g\00e\00\"\00:\00 \00{\00\n\00 \00 \00 \00 \00 \00 \00\"\00d\00e\00s\00k\00t\00o\00p\00s\00\"\00:\00 \005\000\00,\00\n\00 \00 \00 \00 \00 \00 \00\"\00s\00t\00o\00r\00a\00g\00e\00\"\00:\00 \005\000\000\000\00,\00\n\00 \00 \00 \00 \00 \00 \00\"\00b\00a\00n\00d\00w\00i\00d\00t\00h\00\"\00:\00 \001\000\000\000\000\00\n\00 \00 \00 \00 \00}\00\n\00 \00 \00}")
 (data $15 (i32.const 2748) "\1c\00\00\00\03\00\00\00\00\00\00\00\04\00\00\00\0c\00\00\00\e0\08\00\00\00\00\00\00 \t")
 (data $16 (i32.const 2780) "\\")
 (data $16.1 (i32.const 2792) "\02\00\00\00L\00\00\00P\00r\00o\00c\00e\00s\00s\00i\00n\00g\00 \00s\00u\00b\00s\00c\00r\00i\00p\00t\00i\00o\00n\00 \00f\00o\00r\00 \00c\00u\00s\00t\00o\00m\00e\00r\00:\00 ")
 (data $17 (i32.const 2876) "\1c")
 (data $17.1 (i32.const 2888) "\02\00\00\00\0c\00\00\00P\00l\00a\00n\00:\00 ")
 (data $18 (i32.const 2908) "\ec")
 (data $18.1 (i32.const 2920) "\02\00\00\00\d4\00\00\00{\00\n\00 \00 \00 \00 \00\"\00t\00o\00t\00a\00l\00C\00u\00s\00t\00o\00m\00e\00r\00s\00\"\00:\00 \001\005\000\00,\00\n\00 \00 \00 \00 \00\"\00a\00c\00t\00i\00v\00e\00D\00e\00s\00k\00t\00o\00p\00s\00\"\00:\00 \001\002\005\000\00,\00\n\00 \00 \00 \00 \00\"\00r\00e\00v\00e\00n\00u\00e\00\"\00:\00 \001\002\005\000\000\000\00.\000\000\00,\00\n\00 \00 \00 \00 \00\"\00u\00p\00t\00i\00m\00e\00\"\00:\00 \009\009\00.\009\008\00\n\00 \00 \00}")
 (export "init" (func $assembly/index/init))
 (export "getCustomer" (func $assembly/index/getCustomer))
 (export "createDesktop" (func $assembly/index/createDesktop))
 (export "getBillingInfo" (func $assembly/index/getBillingInfo))
 (export "processSubscription" (func $assembly/index/processSubscription))
 (export "getMetrics" (func $assembly/index/getMetrics))
 (export "memory" (memory $0))
 (start $~start)
 (func $assembly/index/init
  i32.const 1056
  call $assembly/index/log
 )
 (func $~lib/rt/stub/__new (param $0 i32) (result i32)
  (local $1 i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  (local $5 i32)
  (local $6 i32)
  local.get $0
  i32.const 1073741804
  i32.gt_u
  if
   i32.const 1872
   i32.const 1936
   i32.const 86
   i32.const 30
   call $~lib/builtins/abort
   unreachable
  end
  local.get $0
  i32.const 16
  i32.add
  local.tee $2
  i32.const 1073741820
  i32.gt_u
  if
   i32.const 1872
   i32.const 1936
   i32.const 33
   i32.const 29
   call $~lib/builtins/abort
   unreachable
  end
  global.get $~lib/rt/stub/offset
  global.get $~lib/rt/stub/offset
  i32.const 4
  i32.add
  local.tee $1
  local.get $2
  i32.const 19
  i32.add
  i32.const -16
  i32.and
  i32.const 4
  i32.sub
  local.tee $5
  i32.add
  local.tee $2
  memory.size
  local.tee $3
  i32.const 16
  i32.shl
  i32.const 15
  i32.add
  i32.const -16
  i32.and
  local.tee $6
  i32.gt_u
  if
   local.get $3
   local.get $2
   local.get $6
   i32.sub
   i32.const 65535
   i32.add
   i32.const -65536
   i32.and
   i32.const 16
   i32.shr_u
   local.tee $6
   local.get $3
   local.get $6
   i32.gt_s
   select
   memory.grow
   i32.const 0
   i32.lt_s
   if
    local.get $6
    memory.grow
    i32.const 0
    i32.lt_s
    if
     unreachable
    end
   end
  end
  local.get $2
  global.set $~lib/rt/stub/offset
  local.get $5
  i32.store
  local.get $1
  i32.const 4
  i32.sub
  local.tee $2
  i32.const 0
  i32.store offset=4
  local.get $2
  i32.const 0
  i32.store offset=8
  local.get $2
  i32.const 2
  i32.store offset=12
  local.get $2
  local.get $0
  i32.store offset=16
  local.get $1
  i32.const 16
  i32.add
 )
 (func $~lib/util/number/itoa32 (param $0 i32) (result i32)
  (local $1 i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  local.get $0
  i32.eqz
  if
   i32.const 1840
   return
  end
  i32.const 0
  local.get $0
  i32.sub
  local.get $0
  local.get $0
  i32.const 31
  i32.shr_u
  i32.const 1
  i32.shl
  local.tee $1
  select
  local.tee $0
  i32.const 10
  i32.ge_u
  i32.const 1
  i32.add
  local.get $0
  i32.const 10000
  i32.ge_u
  i32.const 3
  i32.add
  local.get $0
  i32.const 1000
  i32.ge_u
  i32.add
  local.get $0
  i32.const 100
  i32.lt_u
  select
  local.get $0
  i32.const 1000000
  i32.ge_u
  i32.const 6
  i32.add
  local.get $0
  i32.const 1000000000
  i32.ge_u
  i32.const 8
  i32.add
  local.get $0
  i32.const 100000000
  i32.ge_u
  i32.add
  local.get $0
  i32.const 10000000
  i32.lt_u
  select
  local.get $0
  i32.const 100000
  i32.lt_u
  select
  local.tee $2
  i32.const 1
  i32.shl
  local.get $1
  i32.add
  call $~lib/rt/stub/__new
  local.tee $3
  local.get $1
  i32.add
  local.set $4
  loop $do-loop|0
   local.get $4
   local.get $2
   i32.const 1
   i32.sub
   local.tee $2
   i32.const 1
   i32.shl
   i32.add
   local.get $0
   i32.const 10
   i32.rem_u
   i32.const 48
   i32.add
   i32.store16
   local.get $0
   i32.const 10
   i32.div_u
   local.tee $0
   br_if $do-loop|0
  end
  local.get $1
  if
   local.get $3
   i32.const 45
   i32.store16
  end
  local.get $3
 )
 (func $~lib/staticarray/StaticArray<~lib/string/String>#__uset (param $0 i32) (param $1 i32)
  local.get $0
  local.get $1
  i32.store offset=4
 )
 (func $~lib/string/String#get:length (param $0 i32) (result i32)
  local.get $0
  i32.const 20
  i32.sub
  i32.load offset=16
  i32.const 1
  i32.shr_u
 )
 (func $~lib/string/String.__ne (param $0 i32) (result i32)
  local.get $0
  i32.eqz
  i32.eqz
 )
 (func $~lib/string/String.__concat (param $0 i32) (param $1 i32) (result i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  (local $5 i32)
  i32.const 2096
  local.set $2
  local.get $0
  call $~lib/string/String#get:length
  i32.const 1
  i32.shl
  local.tee $3
  local.get $1
  call $~lib/string/String#get:length
  i32.const 1
  i32.shl
  local.tee $4
  i32.add
  local.tee $5
  if
   local.get $5
   call $~lib/rt/stub/__new
   local.tee $2
   local.get $0
   local.get $3
   memory.copy
   local.get $2
   local.get $3
   i32.add
   local.get $1
   local.get $4
   memory.copy
  end
  local.get $2
 )
 (func $~lib/staticarray/StaticArray<~lib/string/String>#join (param $0 i32) (result i32)
  (local $1 i32)
  (local $2 i32)
  (local $3 i32)
  (local $4 i32)
  (local $5 i32)
  block $__inlined_func$~lib/util/string/joinReferenceArray<~lib/string/String> (result i32)
   i32.const 2096
   local.get $0
   local.tee $1
   i32.const 20
   i32.sub
   i32.load offset=16
   i32.const 2
   i32.shr_u
   i32.const 1
   i32.sub
   local.tee $3
   i32.const 0
   i32.lt_s
   br_if $__inlined_func$~lib/util/string/joinReferenceArray<~lib/string/String>
   drop
   local.get $3
   i32.eqz
   if
    local.get $1
    i32.load
    local.tee $0
    call $~lib/string/String.__ne
    if (result i32)
     local.get $0
    else
     i32.const 2096
    end
    br $__inlined_func$~lib/util/string/joinReferenceArray<~lib/string/String>
   end
   i32.const 2096
   local.set $0
   i32.const 2096
   call $~lib/string/String#get:length
   local.set $4
   loop $for-loop|0
    local.get $2
    local.get $3
    i32.lt_s
    if
     local.get $1
     local.get $2
     i32.const 2
     i32.shl
     i32.add
     i32.load
     local.tee $5
     call $~lib/string/String.__ne
     if
      local.get $0
      local.get $5
      call $~lib/string/String.__concat
      local.set $0
     end
     local.get $4
     if
      local.get $0
      i32.const 2096
      call $~lib/string/String.__concat
      local.set $0
     end
     local.get $2
     i32.const 1
     i32.add
     local.set $2
     br $for-loop|0
    end
   end
   local.get $1
   local.get $3
   i32.const 2
   i32.shl
   i32.add
   i32.load
   local.tee $1
   call $~lib/string/String.__ne
   if (result i32)
    local.get $0
    local.get $1
    call $~lib/string/String.__concat
   else
    local.get $0
   end
  end
 )
 (func $assembly/index/getCustomer (param $0 i32) (result i32)
  i32.const 1616
  local.get $0
  call $~lib/util/number/itoa32
  call $~lib/staticarray/StaticArray<~lib/string/String>#__uset
  i32.const 1616
  call $~lib/staticarray/StaticArray<~lib/string/String>#join
 )
 (func $assembly/index/createDesktop (param $0 i32) (param $1 i32) (result i32)
  i32.const 2128
  local.get $0
  call $~lib/util/number/itoa32
  call $~lib/string/String.__concat
  call $assembly/index/log
  i32.const 2224
  local.get $1
  call $~lib/string/String.__concat
  call $assembly/index/log
  i32.const 12345
 )
 (func $assembly/index/getBillingInfo (param $0 i32) (result i32)
  i32.const 2768
  local.get $0
  call $~lib/util/number/itoa32
  call $~lib/staticarray/StaticArray<~lib/string/String>#__uset
  i32.const 2768
  call $~lib/staticarray/StaticArray<~lib/string/String>#join
 )
 (func $assembly/index/processSubscription (param $0 i32) (param $1 i32) (result i32)
  i32.const 2800
  local.get $0
  call $~lib/util/number/itoa32
  call $~lib/string/String.__concat
  call $assembly/index/log
  i32.const 2896
  local.get $1
  call $~lib/string/String.__concat
  call $assembly/index/log
  i32.const 1
 )
 (func $assembly/index/getMetrics (result i32)
  i32.const 2928
 )
 (func $~start
  i32.const 3148
  global.set $~lib/rt/stub/offset
 )
)
