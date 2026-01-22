import { useAppSecurityStore } from "@/stores/appSecurityStore";
import { Input } from "@/components/Base/Input";
import { Fingerprint, Eye, EyeOff } from "lucide-react";
import { useState, useRef, useEffect } from "react";
import _ from "lodash";

function Main() {
  const inputRef = useRef<HTMLInputElement>(null);
  const { setAppSecurity } = useAppSecurityStore();
  const [pin, setPin] = useState("Left4code");
  const [isForgotPassword, setIsForgotPassword] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/profiles/**/*.{jpg,jpeg,png,svg}", { eager: true });

  useEffect(() => {
    document.querySelectorAll("html")[0].classList.remove("dark");
  }, []);

  return (
    <>
      <div className="fixed inset-0 flex flex-col items-center justify-center gap-5">
        <div className="relative w-20 h-20 overflow-hidden border-2 rounded-full shadow-lg">
          <img
            className="absolute object-cover size-full"
            src={
              imageAssets[
                "/src/assets/images/profiles/" +
                  "profile-" +
                  _.random(1, 2) +
                  ".jpg"
              ].default
            }
          />
        </div>
        <div className="text-lg font-medium text-background [text-shadow:_0px_1px_10px_rgb(0_0_0_/_50%)]">
          Ethan Hunt
        </div>
        <div className="relative">
          <Fingerprint className="absolute inset-y-0 w-5 h-5 my-auto ml-3.5 text-background" />
          <Input
            ref={inputRef}
            className="rounded-full shadow-md px-11 w-80 text-background bg-background/30 border-background/20 placeholder:text-background/80 focus-visible:ring-transparent focus-visible:ring-offset-0"
            type={showPassword ? "text" : "password"}
            value={pin}
            placeholder="Type your PIN to continue..."
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                setAppSecurity({
                  isLoggedIn: true,
                });
              }
            }}
            onChange={(e) => setPin(e.target.value)}
          />
          {!showPassword ? (
            <Eye
              onClick={() => setShowPassword(!showPassword)}
              className="absolute inset-y-0 w-5 h-5 my-auto mr-3.5 right-0 text-background/70 hover:text-background cursor-pointer"
            />
          ) : (
            <EyeOff
              onClick={() => setShowPassword(!showPassword)}
              className="absolute inset-y-0 w-5 h-5 my-auto mr-3.5 right-0 text-background/70 hover:text-background cursor-pointer"
            />
          )}
        </div>
        <a
          href=""
          onClick={(e) => {
            e.preventDefault();
            setPin("Left4code");
            setIsForgotPassword(true);
            inputRef.current?.focus();
            inputRef.current?.select();
          }}
          className="text-background/80 hover:text-background [text-shadow:_0px_1px_10px_rgb(0_0_0_/_50%)]"
        >
          {!isForgotPassword
            ? "Forgot your PIN?"
            : "Type anything and press Enter!"}
        </a>
      </div>
    </>
  );
}

export default Main;
