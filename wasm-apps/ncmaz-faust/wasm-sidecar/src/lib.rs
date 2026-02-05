use include_dir::{include_dir, Dir};
use once_cell::sync::Lazy;
use std::sync::Mutex;
use wasm_bindgen::prelude::*;

#[global_allocator]
static ALLOC: wee_alloc::WeeAlloc = wee_alloc::WeeAlloc::INIT;

static OUT_DIR: Dir = include_dir!("$CARGO_MANIFEST_DIR/../out");

fn collect_file_paths(dir: &Dir, paths: &mut Vec<Vec<u8>>) {
    for file in dir.files() {
        let path = format!("/{}", file.path().to_string_lossy());
        let mut bytes = path.into_bytes();
        bytes.push(0);
        paths.push(bytes);
    }

    for subdir in dir.dirs() {
        collect_file_paths(subdir, paths);
    }
}

static FILE_PATHS: Lazy<Vec<Vec<u8>>> = Lazy::new(|| {
    let mut paths = Vec::new();
    collect_file_paths(&OUT_DIR, &mut paths);
    paths
});

static LAST_FILE: Lazy<Mutex<Vec<u8>>> = Lazy::new(|| Mutex::new(Vec::new()));
static LAST_FILE_MIME: Lazy<Mutex<Vec<u8>>> = Lazy::new(|| Mutex::new(Vec::new()));
static WP_DATA: Lazy<Mutex<Vec<u8>>> = Lazy::new(|| Mutex::new(Vec::new()));

fn mime_for_path(path: &str) -> &'static str {
    if path.ends_with(".html") {
        "text/html"
    } else if path.ends_with(".js") {
        "text/javascript"
    } else if path.ends_with(".css") {
        "text/css"
    } else if path.ends_with(".json") {
        "application/json"
    } else if path.ends_with(".svg") {
        "image/svg+xml"
    } else if path.ends_with(".png") {
        "image/png"
    } else if path.ends_with(".jpg") || path.ends_with(".jpeg") {
        "image/jpeg"
    } else if path.ends_with(".webp") {
        "image/webp"
    } else if path.ends_with(".woff2") {
        "font/woff2"
    } else if path.ends_with(".woff") {
        "font/woff"
    } else if path.ends_with(".ttf") {
        "font/ttf"
    } else {
        "application/octet-stream"
    }
}

#[wasm_bindgen]
pub fn alloc(len: usize) -> *mut u8 {
    let mut buf = Vec::with_capacity(len);
    let ptr = buf.as_mut_ptr();
    std::mem::forget(buf);
    ptr
}

#[wasm_bindgen]
pub fn dealloc(ptr: *mut u8, len: usize) {
    unsafe {
        let _ = Vec::from_raw_parts(ptr, 0, len);
    }
}

#[wasm_bindgen]
pub fn get_file_count() -> usize {
    FILE_PATHS.len()
}

#[wasm_bindgen]
pub fn get_file_path_ptr(index: usize) -> *const u8 {
    FILE_PATHS
        .get(index)
        .map(|v| v.as_ptr())
        .unwrap_or(std::ptr::null())
}

#[wasm_bindgen]
pub fn get_file_path_len(index: usize) -> usize {
    FILE_PATHS.get(index).map(|v| v.len() - 1).unwrap_or(0)
}

#[wasm_bindgen]
pub fn get_file(path_ptr: *const u8, path_len: usize) -> i32 {
    if path_ptr.is_null() || path_len == 0 {
        return 0;
    }

    let path = unsafe { std::slice::from_raw_parts(path_ptr, path_len) };
    let path = match std::str::from_utf8(path) {
        Ok(p) => p,
        Err(_) => return 0,
    };

    let path = path.strip_prefix('/').unwrap_or(path);
    let file = match OUT_DIR.get_file(path) {
        Some(file) => file,
        None => return 0,
    };

    {
        let mut last = LAST_FILE.lock().unwrap();
        last.clear();
        last.extend_from_slice(file.contents());
    }

    {
        let mime = mime_for_path(path);
        let mut last_mime = LAST_FILE_MIME.lock().unwrap();
        last_mime.clear();
        last_mime.extend_from_slice(mime.as_bytes());
        last_mime.push(0);
    }

    1
}

#[wasm_bindgen]
pub fn get_last_file_ptr() -> *const u8 {
    LAST_FILE.lock().unwrap().as_ptr()
}

#[wasm_bindgen]
pub fn get_last_file_len() -> usize {
    LAST_FILE.lock().unwrap().len()
}

#[wasm_bindgen]
pub fn get_last_file_mime_ptr() -> *const u8 {
    LAST_FILE_MIME.lock().unwrap().as_ptr()
}

#[wasm_bindgen]
pub fn get_last_file_mime_len() -> usize {
    let len = LAST_FILE_MIME.lock().unwrap().len();
    if len == 0 {
        0
    } else {
        len - 1
    }
}

#[wasm_bindgen]
pub fn receive_wp_data(ptr: *const u8, len: usize) -> i32 {
    if ptr.is_null() || len == 0 {
        return 0;
    }

    let bytes = unsafe { std::slice::from_raw_parts(ptr, len) };
    let mut data = WP_DATA.lock().unwrap();
    data.clear();
    data.extend_from_slice(bytes);
    1
}

#[wasm_bindgen]
pub fn get_wp_data_ptr() -> *const u8 {
    WP_DATA.lock().unwrap().as_ptr()
}

#[wasm_bindgen]
pub fn get_wp_data_len() -> usize {
    WP_DATA.lock().unwrap().len()
}
