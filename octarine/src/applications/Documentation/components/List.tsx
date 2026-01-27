import Paragraph, { type MainProps as ParagraphProps } from "./Paragraph";

interface UlProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.HTMLAttributes<HTMLUListElement>,
      HTMLUListElement
    >
  > {}

const List = ({ children, ...props }: UlProps) => {
  return (
    <ul {...props} className="flex flex-col gap-3 pl-5 list-disc">
      {children}
    </ul>
  );
};

interface LiProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.LiHTMLAttributes<HTMLLIElement>,
      HTMLLIElement
    >
  > {}

const Item = ({ children, ...props }: LiProps) => {
  return (
    <li {...props} className="[&::marker]:text-muted-foreground/70 pl-1">
      {children}
    </li>
  );
};

interface TitleProps
  extends React.PropsWithChildren<
    React.DetailedHTMLProps<
      React.HTMLAttributes<HTMLDivElement>,
      HTMLDivElement
    >
  > {}

const Title = ({ children, ...props }: TitleProps) => {
  return (
    <div {...props} className="font-medium opacity-90">
      {children}
    </div>
  );
};

const Content = ({ children, ...props }: ParagraphProps) => {
  return (
    <Paragraph {...props} className="mt-1">
      {children}
    </Paragraph>
  );
};

const ListComponent = Object.assign(List, {
  Item,
  Title,
  Content,
});

export default ListComponent;
