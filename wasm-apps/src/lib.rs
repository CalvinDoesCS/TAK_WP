use wasm_bindgen::prelude::*;
use web_sys::console;

/// Greet function - simple example
#[wasm_bindgen]
pub fn greet(name: &str) -> String {
    format!("Hello, {}! from WASM", name)
}

/// Initialize WASM app - called when app loads
#[wasm_bindgen]
pub fn init_app() {
    console::log_1(&"WASM App initialized!".into());
}

/// Calculate fibonacci - demonstrates computation
#[wasm_bindgen]
pub fn fibonacci(n: u32) -> u32 {
    if n <= 1 {
        return n;
    }
    let mut a = 0;
    let mut b = 1;
    for _ in 2..=n {
        let temp = a + b;
        a = b;
        b = temp;
    }
    b
}

/// Process data - example of passing complex data
#[wasm_bindgen]
pub fn process_data(data: &str) -> String {
    console::log_1(&format!("Processing: {}", data).into());
    
    let result = data
        .to_uppercase()
        .chars()
        .rev()
        .collect::<String>();
    
    result
}
