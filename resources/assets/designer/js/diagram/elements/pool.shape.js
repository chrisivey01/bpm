/**
 * Task Shape class
 */
export class PoolShape {
    constructor(canvas, svgLoader) {
        this.canvas = canvas;
        this.svgLoader = svgLoader;
        this.rounded = 5;
        this.attr = {
            fill: '#FFF',
            stroke: '#000',
            strokeWidth: 2
        };
        this.shape = this.canvas.group();
    }

    config(options) {
        this.options = options;
        this.id = options.act_uid;
        this.x = +options.x;
        this.y = +options.y;
        this.scaleX = +options.width;
        this.scaleY = +options.height;
        this.rounded = +options.rounded || this.rounded;
        this.attr = options.attr || this.attr;
    }

    render() {
        this.shape.add(this.canvas.rect(
            this.x,
            this.y,
            this.scaleX,
            this.scaleY,
            this.rounded
        ).attr(this.attr));

        this.addDecorators();
        this.shape.drag();
    }

    addDecorators() {
        this.addLineDecorator();
        this.addTextDecorator();
    }

    addLineDecorator() {
        this.shape.add(
            this.canvas.polyline(`${this.x + 42} ${this.y} ${this.x + 42} ${this.y + this.scaleY}`).attr({
                fill: 'none',
                stroke: '#000',
                strokeWidth: 2
            })
        );
    }

    addTextDecorator() {
        const text = this.canvas.text(
            this.x,
            this.y,
            this.options.name,
            {'font-size': '13px', 'font-family': 'Arial, Helvetica, sans-serif'})
        const textBox = text.getBBox();
        const tx = textBox.height + (42 - textBox.height) / 2;
        const ty = textBox.width + (this.scaleY - textBox.width) / 2;
        text.transform('r270 ' + this.x + ', ' + this.y + ' t' + -ty + ', ' + tx);
        this.shape.add(text);
    }

    getNativeShape() {
        return this.shape;
    }
}