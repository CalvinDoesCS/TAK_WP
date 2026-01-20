import { cn } from "@/lib/utils";
import SyntaxHighlighter from "react-syntax-highlighter";
import { dracula } from "react-syntax-highlighter/dist/esm/styles/hljs";

export interface MainProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.HTMLAttributes<HTMLDivElement>,
      HTMLDivElement
    >
  > {
  children: string;
  language?: string;
  showLineNumbers?: boolean;
  wrapLines?: boolean;
}

function Main({
  children,
  language = "tsx",
  showLineNumbers = true,
  wrapLines = true,
  ...props
}: MainProps) {
  return (
    <div
      className={cn([
        props.className,
        "[&_pre]:!bg-muted [&_pre]:scrollbar mt-2.5 [&>pre]:rounded-md [&>pre]:!px-2 [&_.react-syntax-highlighter-line-number]:text-foreground/50 [&_.react-syntax-highlighter-line-number]:mr-2 [&_.react-syntax-highlighter-line-number]:!min-w-8 [&_.react-syntax-highlighter-line-number]:!pl-2",
      ])}
    >
      <SyntaxHighlighter
        language={language}
        style={dracula}
        wrapLines={wrapLines}
        showLineNumbers={showLineNumbers}
      >
        {children}
      </SyntaxHighlighter>
    </div>
  );
}

export default Main;
