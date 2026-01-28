//! Demo Room - A complete WASM application
//! 
//! This demonstrates embedding a full application in WASM:
//! - State management (rooms, devices)
//! - Event handling
//! - Complex logic compiled to WASM
//! 
//! Build: cargo build --release --target wasm32-unknown-unknown

#![no_std]

// ============================================================================
// Core data structures (all Copy-compatible for static storage)
// ============================================================================

#[repr(C)]
#[derive(Clone, Debug, Copy)]
struct Device {
    id: u32,
    status: u8,      // 0 = off, 1 = on
    power_usage: u32, // watts
}

#[repr(C)]
#[derive(Clone, Debug, Copy)]
struct RoomData {
    id: u32,
    name: [u8; 64],
    name_len: u32,
    lighting: u32,
    temperature: u32,
    device_ids: [u32; 8],      // Device IDs (0 = unused)
    device_status: [u8; 8],    // 0 = off, 1 = on, 255 = unused
    device_power: [u32; 8],    // Watts per device
    device_count: u32,
    status: u8,
}

impl RoomData {
    fn default() -> Self {
        RoomData {
            id: 0,
            name: [0; 64],
            name_len: 0,
            lighting: 50,
            temperature: 21,
            device_ids: [0; 8],
            device_status: [255; 8], // 255 = unused
            device_power: [0; 8],
            device_count: 0,
            status: 0,
        }
    }
}

// ============================================================================
// Global state
// ============================================================================

static mut ROOMS: [RoomData; 16] = [RoomData {
    id: 0,
    name: [0; 64],
    name_len: 0,
    lighting: 50,
    temperature: 21,
    device_ids: [0; 8],
    device_status: [255; 8],
    device_power: [0; 8],
    device_count: 0,
    status: 0,
}; 16];

static mut ROOM_COUNT: u32 = 0;
static mut NEXT_ROOM_ID: u32 = 1;
static mut NEXT_DEVICE_ID: u32 = 100;

// ============================================================================
// Panic handler for WASM
// ============================================================================

#[panic_handler]
fn panic_handler(_info: &core::panic::PanicInfo) -> ! {
    loop {}
}

// ============================================================================
// Core functions
// ============================================================================

#[no_mangle]
pub extern "C" fn init_room() {
    // Initialize the demo room application
    unsafe {
        ROOM_COUNT = 0;
        NEXT_ROOM_ID = 1;
        NEXT_DEVICE_ID = 100;

        // Create a demo room with some devices
        create_room_internal(b"Living Room", 11);

        if ROOM_COUNT > 0 {
            let room = &mut ROOMS[0];
            // Add lamp device (on, 60W)
            if room.device_count < 8 {
                let idx = room.device_count as usize;
                room.device_ids[idx] = NEXT_DEVICE_ID;
                room.device_status[idx] = 1;
                room.device_power[idx] = 60;
                NEXT_DEVICE_ID += 1;
                room.device_count += 1;
            }

            // Add AC device (off, 3500W)
            if room.device_count < 8 {
                let idx = room.device_count as usize;
                room.device_ids[idx] = NEXT_DEVICE_ID;
                room.device_status[idx] = 0;
                room.device_power[idx] = 3500;
                NEXT_DEVICE_ID += 1;
                room.device_count += 1;
            }

            // Add TV device (off, 100W)
            if room.device_count < 8 {
                let idx = room.device_count as usize;
                room.device_ids[idx] = NEXT_DEVICE_ID;
                room.device_status[idx] = 0;
                room.device_power[idx] = 100;
                NEXT_DEVICE_ID += 1;
                room.device_count += 1;
            }
        }
    }
}

#[no_mangle]
pub extern "C" fn create_room(_name_ptr: u32, _name_len: u32) -> u32 {
    unsafe { create_room_internal(b"New Room", 8) }
}

unsafe fn create_room_internal(name: &[u8], name_len: usize) -> u32 {
    if ROOM_COUNT >= 16 {
        return 0; // Can't create more rooms
    }

    let idx = ROOM_COUNT as usize;
    let mut room = RoomData::default();

    room.id = NEXT_ROOM_ID;

    // Copy name
    let copy_len = name_len.min(64);
    for i in 0..copy_len {
        room.name[i] = name[i];
    }
    room.name_len = copy_len as u32;

    ROOMS[idx] = room;
    ROOM_COUNT += 1;
    NEXT_ROOM_ID += 1;

    NEXT_ROOM_ID - 1
}

#[no_mangle]
pub extern "C" fn set_lighting(room_id: u32, level: u32) {
    let level = level.min(100);
    unsafe {
        for room in &mut ROOMS {
            if room.id == room_id {
                room.lighting = level;
                room.status = 1; // Mark as active
                return;
            }
        }
    }
}

#[no_mangle]
pub extern "C" fn set_temperature(room_id: u32, temp: u32) {
    let temp = temp.max(16).min(30);
    unsafe {
        for room in &mut ROOMS {
            if room.id == room_id {
                room.temperature = temp;
                room.status = 1;
                return;
            }
        }
    }
}

#[no_mangle]
pub extern "C" fn toggle_device(room_id: u32, device_id: u32) {
    unsafe {
        for room in &mut ROOMS {
            if room.id == room_id {
                // Find device by ID
                for i in 0..room.device_count as usize {
                    if room.device_ids[i] == device_id && room.device_status[i] != 255 {
                        room.device_status[i] = if room.device_status[i] == 0 { 1 } else { 0 };
                        room.status = 1;
                        return;
                    }
                }
                return;
            }
        }
    }
}

#[no_mangle]
pub extern "C" fn get_total_power(room_id: u32) -> u32 {
    unsafe {
        for room in &ROOMS {
            if room.id == room_id {
                let mut total = 0u32;
                for i in 0..room.device_count as usize {
                    if room.device_status[i] == 1 {
                        total = total.saturating_add(room.device_power[i]);
                    }
                }
                return total;
            }
        }
    }
    0
}

#[no_mangle]
pub extern "C" fn get_room_count() -> u32 {
    unsafe { ROOM_COUNT }
}

// ============================================================================
// JSON output
// ============================================================================

#[no_mangle]
pub extern "C" fn get_state_json(room_id: u32) -> *const u8 {
    static mut JSON_BUFFER: [u8; 4096] = [0; 4096];

    unsafe {
        for room in &ROOMS {
            if room.id == room_id {
                let mut pos = 0;

                // Start JSON
                pos = write_to_buffer(&mut JSON_BUFFER, pos, b"{\"id\":1,\"name\":\"");

                // Room name
                let name_len = room.name_len.min(64) as usize;
                pos = write_to_buffer(&mut JSON_BUFFER, pos, &room.name[0..name_len]);

                pos = write_to_buffer(&mut JSON_BUFFER, pos, b"\",\"lighting\":");
                pos = write_number(&mut JSON_BUFFER, pos, room.lighting as i32);

                pos = write_to_buffer(&mut JSON_BUFFER, pos, b",\"temperature\":");
                pos = write_number(&mut JSON_BUFFER, pos, room.temperature as i32);

                pos = write_to_buffer(&mut JSON_BUFFER, pos, b",\"status\":\"");

                let status_str = match room.status {
                    0 => &b"idle"[..],
                    1 => &b"active"[..],
                    _ => &b"offline"[..],
                };
                pos = write_to_buffer(&mut JSON_BUFFER, pos, status_str);

                pos = write_to_buffer(&mut JSON_BUFFER, pos, b"\",\"devices\":[");

                for i in 0..room.device_count as usize {
                    if i > 0 {
                        pos = write_to_buffer(&mut JSON_BUFFER, pos, b",");
                    }

                    pos = write_to_buffer(&mut JSON_BUFFER, pos, b"{\"id\":");
                    pos = write_number(&mut JSON_BUFFER, pos, room.device_ids[i] as i32);

                    pos = write_to_buffer(&mut JSON_BUFFER, pos, b",\"name\":\"Device ");
                    pos = write_number(&mut JSON_BUFFER, pos, room.device_ids[i] as i32);

                    pos = write_to_buffer(&mut JSON_BUFFER, pos, b"\",\"status\":\"");

                    if room.device_status[i] == 0 {
                        pos = write_to_buffer(&mut JSON_BUFFER, pos, b"off");
                    } else if room.device_status[i] == 1 {
                        pos = write_to_buffer(&mut JSON_BUFFER, pos, b"on");
                    }

                    pos = write_to_buffer(&mut JSON_BUFFER, pos, b"\",\"power\":");
                    pos = write_number(&mut JSON_BUFFER, pos, room.device_power[i] as i32);

                    pos = write_to_buffer(&mut JSON_BUFFER, pos, b"}");
                }

                pos = write_to_buffer(&mut JSON_BUFFER, pos, b"]}");

                // Null terminate
                if pos < 4096 {
                    JSON_BUFFER[pos] = 0;
                }

                return JSON_BUFFER.as_ptr();
            }
        }
    }

    // Return empty JSON on error
    b"{}\0".as_ptr()
}

// ============================================================================
// Helper functions
// ============================================================================

fn write_to_buffer(buffer: &mut [u8; 4096], pos: usize, data: &[u8]) -> usize {
    let mut new_pos = pos;
    for &b in data {
        if new_pos < 4095 {
            buffer[new_pos] = b;
            new_pos += 1;
        }
    }
    new_pos
}

fn write_number(buffer: &mut [u8; 4096], pos: usize, n: i32) -> usize {
    if n == 0 {
        return write_to_buffer(buffer, pos, b"0");
    }

    let mut num = n.abs() as u32;
    let mut digits = [0u8; 10];
    let mut len = 0;

    while num > 0 {
        digits[len] = b'0' + (num % 10) as u8;
        num /= 10;
        len += 1;
    }

    let mut new_pos = pos;

    if n < 0 {
        new_pos = write_to_buffer(buffer, new_pos, b"-");
    }

    for i in (0..len).rev() {
        new_pos = write_to_buffer(buffer, new_pos, &[digits[i]]);
    }

    new_pos
}
