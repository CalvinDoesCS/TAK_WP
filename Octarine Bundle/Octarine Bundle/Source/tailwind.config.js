/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: ["class"],
  content: [
    "./pages/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
    "./app/**/*.{ts,tsx}",
    "./src/**/*.{ts,tsx}",
  ],
  prefix: "",
  theme: {
    container: {
      center: true,
      padding: "2rem",
      screens: {
        "2xl": "1400px",
      },
    },
    extend: {
      colors: {
        border: "hsl(var(--border))",
        input: "hsl(var(--input))",
        ring: "hsl(var(--ring))",
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",
        primary: {
          DEFAULT: "hsl(var(--primary))",
          foreground: "hsl(var(--primary-foreground))",
        },
        secondary: {
          DEFAULT: "hsl(var(--secondary))",
          foreground: "hsl(var(--secondary-foreground))",
        },
        destructive: {
          DEFAULT: "hsl(var(--destructive))",
          foreground: "hsl(var(--destructive-foreground))",
        },
        muted: {
          DEFAULT: "hsl(var(--muted))",
          foreground: "hsl(var(--muted-foreground))",
        },
        accent: {
          DEFAULT: "hsl(var(--accent))",
          foreground: "hsl(var(--accent-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--popover))",
          foreground: "hsl(var(--popover-foreground))",
        },
        card: {
          DEFAULT: "hsl(var(--card))",
          foreground: "hsl(var(--card-foreground))",
        },
        sidebar: {
          DEFAULT: "hsl(var(--sidebar-background))",
          foreground: "hsl(var(--sidebar-foreground))",
          primary: "hsl(var(--sidebar-primary))",
          "primary-foreground": "hsl(var(--sidebar-primary-foreground))",
          accent: "hsl(var(--sidebar-accent))",
          "accent-foreground": "hsl(var(--sidebar-accent-foreground))",
          border: "hsl(var(--sidebar-border))",
          ring: "hsl(var(--sidebar-ring))",
        },
      },
      fontFamily: {
        roboto: ["Roboto"],
        orbitron: ["Orbitron"],
      },
      backgroundImage: {
        "empty-directory":
          "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='48.117' height='43.814' viewBox='0 0 48.117 43.814'%3E%3Cdefs%3E%3ClinearGradient id='linear-gradient' x1='1.122' y1='1.148' x2='0.157' y2='0.186' gradientUnits='objectBoundingBox'%3E%3Cstop offset='0'/%3E%3Cstop offset='0.159' stop-opacity='0.431'/%3E%3Cstop offset='0.194' stop-opacity='0.102'/%3E%3Cstop offset='1' stop-opacity='0'/%3E%3C/linearGradient%3E%3CclipPath id='clip-path'%3E%3Cpath id='Path_12' data-name='Path 12' d='M16.968,10.878c-7.6,0-8.376.418-8.376,7.8v10.61H56.707v-6.5c0-7.384-.779-8.561-8.376-8.561H29.642L26.2,10.88Z' fill='%2360a5fa'/%3E%3C/clipPath%3E%3CradialGradient id='radial-gradient' cx='0.5' cy='0.142' r='1.951' gradientTransform='translate(8.372 -53.714) scale(0.256 0.254)' gradientUnits='objectBoundingBox'%3E%3Cstop offset='0' stop-color='%23fffefe'/%3E%3Cstop offset='1' stop-color='%23fffefe' stop-opacity='0.204'/%3E%3C/radialGradient%3E%3CradialGradient id='radial-gradient-2' cx='0.482' cy='0.231' r='1.982' gradientTransform='translate(8.227 -48.076) scale(0.252 0.246)' xlink:href='%23radial-gradient'/%3E%3C/defs%3E%3Cg id='Group_2' data-name='Group 2' transform='translate(-9.555 -12.458)'%3E%3Cg id='Group_3' data-name='Group 3'%3E%3Cpath id='Path_7' data-name='Path 7' d='M11.108,3.186c-7.6,0-8.376.418-8.376,7.8v25.9c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V15.094c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,3.186Z' transform='translate(6.824 9.782)' opacity='0.1'/%3E%3Cpath id='Path_8' data-name='Path 8' d='M11.108,2.977c-7.6,0-8.376.418-8.376,7.8V38.348c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V14.885c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,2.976Z' transform='translate(6.824 9.782)' opacity='0.1'/%3E%3Cpath id='Path_9' data-name='Path 9' d='M11.108,2.768c-7.6,0-8.376.418-8.376,7.8V38.139c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V14.675c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,2.767Z' transform='translate(6.824 9.782)' opacity='0.4' fill='url(%23linear-gradient)'/%3E%3Cpath id='Path_10' data-name='Path 10' d='M17.93,12.46c-7.6,0-8.375.507-8.375,7.89V30.96h48.1v-6.5c0-7.383-.776-8.763-8.372-8.763H30.4l-3.238-3.239Z' fill='%233b82f6'/%3E%3Cg id='Group_2-2' data-name='Group 2' transform='translate(0.964 3.343)' clip-path='url(%23clip-path)'%3E%3Cpath id='Path_11' data-name='Path 11' d='M16.968,19.246c-7.6,0-8.376.758-8.376,8.143V44.576c0,7.384.779,8.143,8.376,8.143H48.331c7.6,0,8.376-.758,8.376-8.143V24.042c0-7.384-.779-8.143-8.376-8.143H30.878l-3.439,3.347Z' fill='%23fe4f44' opacity='0.5'/%3E%3C/g%3E%3Cpath id='Path_13' data-name='Path 13' d='M-72.4,903.744c-7.6,0-8.375,1.194-8.375,8.579V929.51c0,7.386.779,8.143,8.375,8.143h31.36c7.6,0,8.372-.757,8.372-8.143V908.978c0-7.386-.779-8.6-8.372-8.463H-58.494l-3.437,3.23Z' transform='translate(90.332 -881.59)' fill='%236baaf7'/%3E%3Cpath id='Path_14' data-name='Path 14' d='M-72.4,894.051c-7.6,0-8.375.507-8.375,7.892v.418c0-7.386.779-7.5,8.375-7.5h9.236l3.237,3.23h18.885c7.6,0,8.375.992,8.375,8.378v-.421c0-7.383-.776-8.763-8.372-8.763h-18.85l-3.246-3.233Z' transform='translate(90.332 -881.59)' opacity='0.4' fill='url(%23radial-gradient)'/%3E%3Cpath id='Path_15' data-name='Path 15' d='M-58.494,900.514l-3.437,3.23H-72.4c-7.6,0-8.375,1.194-8.375,8.579v.418c0-7.383.779-8.189,8.375-8.189h10.47l3.437-3.233H-41.04c7.6,0,8.372.69,8.372,8.076v-.418c0-7.386-.776-8.463-8.372-8.463Z' transform='translate(90.332 -881.59)' opacity='0.4' fill='url(%23radial-gradient-2)'/%3E%3Cpath id='Path_17' data-name='Path 17' d='M9.555,47.5v.419c0,7.382.779,8.16,8.375,8.16H49.292c7.6,0,8.372-.778,8.372-8.16V47.5c0,7.383-.776,7.774-8.372,7.774H17.93C10.33,55.274,9.555,54.883,9.555,47.5Z' fill='%23111110' opacity='0.2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E \")",
        directory:
          "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='48.117' height='43.814' viewBox='0 0 48.117 43.814'%3E%3Cdefs%3E%3ClinearGradient id='linear-gradient' x1='1.122' y1='1.148' x2='0.157' y2='0.186' gradientUnits='objectBoundingBox'%3E%3Cstop offset='0'/%3E%3Cstop offset='0.159' stop-opacity='0.431'/%3E%3Cstop offset='0.194' stop-opacity='0.102'/%3E%3Cstop offset='1' stop-opacity='0'/%3E%3C/linearGradient%3E%3CclipPath id='clip-path'%3E%3Cpath id='Path_12' data-name='Path 12' d='M16.968,10.878c-7.6,0-8.376.418-8.376,7.8v10.61H56.707v-6.5c0-7.384-.779-8.561-8.376-8.561H29.642L26.2,10.88Z' fill='%2360a5fa'/%3E%3C/clipPath%3E%3CradialGradient id='radial-gradient' cx='0.5' cy='0.142' r='1.951' gradientTransform='translate(8.372 -53.714) scale(0.256 0.254)' gradientUnits='objectBoundingBox'%3E%3Cstop offset='0' stop-color='%23fffefe'/%3E%3Cstop offset='1' stop-color='%23fffefe' stop-opacity='0.204'/%3E%3C/radialGradient%3E%3CradialGradient id='radial-gradient-2' cx='0.482' cy='0.231' r='1.982' gradientTransform='translate(8.227 -48.076) scale(0.252 0.246)' xlink:href='%23radial-gradient'/%3E%3C/defs%3E%3Cg id='Group_2' data-name='Group 2' transform='translate(-9.555 -12.458)'%3E%3Cg id='Group_3' data-name='Group 3'%3E%3Cpath id='Path_7' data-name='Path 7' d='M11.108,3.186c-7.6,0-8.376.418-8.376,7.8v25.9c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V15.094c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,3.186Z' transform='translate(6.824 9.782)' opacity='0.1'/%3E%3Cpath id='Path_8' data-name='Path 8' d='M11.108,2.977c-7.6,0-8.376.418-8.376,7.8V38.348c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V14.885c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,2.976Z' transform='translate(6.824 9.782)' opacity='0.1'/%3E%3Cpath id='Path_9' data-name='Path 9' d='M11.108,2.768c-7.6,0-8.376.418-8.376,7.8V38.139c0,7.384.779,8.142,8.376,8.142H42.472c7.6,0,8.376-.758,8.376-8.142V14.675c0-7.384-.779-8.561-8.376-8.561H23.783L20.346,2.767Z' transform='translate(6.824 9.782)' opacity='0.4' fill='url(%23linear-gradient)'/%3E%3Cpath id='Path_10' data-name='Path 10' d='M17.93,12.46c-7.6,0-8.375.507-8.375,7.89V30.96h48.1v-6.5c0-7.383-.776-8.763-8.372-8.763H30.4l-3.238-3.239Z' fill='%233b82f6'/%3E%3Cg id='Group_2-2' data-name='Group 2' transform='translate(0.964 3.343)' clip-path='url(%23clip-path)'%3E%3Cpath id='Path_11' data-name='Path 11' d='M16.968,19.246c-7.6,0-8.376.758-8.376,8.143V44.576c0,7.384.779,8.143,8.376,8.143H48.331c7.6,0,8.376-.758,8.376-8.143V24.042c0-7.384-.779-8.143-8.376-8.143H30.878l-3.439,3.347Z' fill='%23fe4f44' opacity='0.5'/%3E%3C/g%3E%3Cpath id='Path_13' data-name='Path 13' d='M-72.4,903.744c-7.6,0-8.375,1.194-8.375,8.579V929.51c0,7.386.779,8.143,8.375,8.143h31.36c7.6,0,8.372-.757,8.372-8.143V908.978c0-7.386-.779-8.6-8.372-8.463H-58.494l-3.437,3.23Z' transform='translate(90.332 -881.59)' fill='%236baaf7'/%3E%3Cpath id='Path_14' data-name='Path 14' d='M-72.4,894.051c-7.6,0-8.375.507-8.375,7.892v.418c0-7.386.779-7.5,8.375-7.5h9.236l3.237,3.23h18.885c7.6,0,8.375.992,8.375,8.378v-.421c0-7.383-.776-8.763-8.372-8.763h-18.85l-3.246-3.233Z' transform='translate(90.332 -881.59)' opacity='0.4' fill='url(%23radial-gradient)'/%3E%3Cpath id='Path_15' data-name='Path 15' d='M-58.494,900.514l-3.437,3.23H-72.4c-7.6,0-8.375,1.194-8.375,8.579v.418c0-7.383.779-8.189,8.375-8.189h10.47l3.437-3.233H-41.04c7.6,0,8.372.69,8.372,8.076v-.418c0-7.386-.776-8.463-8.372-8.463Z' transform='translate(90.332 -881.59)' opacity='0.4' fill='url(%23radial-gradient-2)'/%3E%3Cpath id='Path_17' data-name='Path 17' d='M9.555,47.5v.419c0,7.382.779,8.16,8.375,8.16H49.292c7.6,0,8.372-.778,8.372-8.16V47.5c0,7.383-.776,7.774-8.372,7.774H17.93C10.33,55.274,9.555,54.883,9.555,47.5Z' fill='%23111110' opacity='0.2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E \")",
        file: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='628.027' height='786.014' viewBox='0 0 628.027 786.014'%3E%3Cg id='Group_5' data-name='Group 5' transform='translate(-646 -92.986)'%3E%3Cpath id='Union_2' data-name='Union 2' d='M40,786A40,40,0,0,1,0,746V40A40,40,0,0,1,40,0H501V103h29v24h98V746a40,40,0,0,1-40,40Z' transform='translate(646 93)' fill='%2364748b'/%3E%3Cpath id='Intersection_2' data-name='Intersection 2' d='M.409,162.042l.058-109.9c31.6,29.739,125.37,125.377,125.37,125.377l-109.976.049A20.025,20.025,0,0,1,.409,162.042Z' transform='translate(1147 42)' fill='%23334155' stroke='%23334155' stroke-width='1'/%3E%3C/g%3E%3C/svg%3E%0A\");",
      },
      borderRadius: {
        lg: "var(--radius)",
        md: "calc(var(--radius) - 2px)",
        sm: "calc(var(--radius) - 4px)",
      },
      keyframes: {
        "accordion-down": {
          from: { height: "0" },
          to: { height: "var(--radix-accordion-content-height)" },
        },
        "accordion-up": {
          from: { height: "var(--radix-accordion-content-height)" },
          to: { height: "0" },
        },
      },
      animation: {
        "accordion-down": "accordion-down 0.2s ease-out",
        "accordion-up": "accordion-up 0.2s ease-out",
      },
    },
  },
  plugins: [
    require("@tailwindcss/typography"),
    require("@tailwindcss/container-queries"),
    require("tailwindcss-animate"),
    require("tailwind-scrollbar"),
  ],
};
