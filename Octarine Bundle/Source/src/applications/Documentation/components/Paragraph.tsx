import { cn } from "@/lib/utils";

export interface MainProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.HTMLAttributes<HTMLDivElement>,
      HTMLDivElement
    >
  > {}

function Main({ children, ...props }: MainProps) {
  return (
    <div
      {...props}
      className={cn([props.className, "text-muted-foreground leading-relaxed"])}
    >
      {children}
    </div>
  );
}

export default Main;
